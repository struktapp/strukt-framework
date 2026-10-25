<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Templator;

/**
 * Generates the module loader class for an application.
 *
 * Scans `rel_appsrc` for module directories and renders the shipped loader
 * template (`templates/lib/App/Loader.sgf`) with one `register()` call per
 * A custom template may be supplied through the optional `rel_loader_sgf`
 * environment key.
 *
 * ```php
 * $loader = (string) new Reloader();
 * ```
 */
final class Reloader
{
    /** Rendered loader source. */
    private string $loader_output;

    public function __construct()
    {
        $root_dir = (string) env('root_dir');
        $app_dir = (string) env('rel_appsrc');
        $loader_sgf_file = Env::has('rel_loader_sgf')
            ? (string) env('rel_loader_sgf')
            : (string) Templates::path('lib/App/Loader.sgf');

        $appsrc_path = sprintf('%s/%s', $root_dir, $app_dir);
        if (!Fs::isPath($appsrc_path)) {
            raise(sprintf('Application source path [%s] does not exist!', $appsrc_path));
        }

        $modules = [];
        foreach (Fs::ls($appsrc_path) as $src_item) {
            if (preg_match('/(\.php|\.)/', $src_item) === 1) {
                continue;
            }

            foreach (Fs::ls(sprintf('%s/%s', $appsrc_path, $src_item)) as $app_item) {
                if (preg_match('/(\.php|\.)/', $app_item) === 1) {
                    continue;
                }

                $modules[$src_item][] = $app_item;
            }
        }

        $register = [];
        foreach ($modules as $name => $mods) {
            foreach ($mods as $mod) {
                if (preg_match('/[A-Za-z]+Module$/', $mod) === 1) {
                    $register[] = sprintf(
                        '$this->app->register(new \\%s\\%s\\%s%s());',
                        $name,
                        $mod,
                        $name,
                        $mod,
                    );
                }
            }
        }

        if (!Fs::isFile($loader_sgf_file)) {
            raise(sprintf('File [%s] was not found!', $loader_sgf_file));
        }

        $template = (string) Fs::cat($loader_sgf_file);
        $this->loader_output = Templator::create($template, [
            'bundles' => implode("\n        ", $register),
        ]);
    }

    /**
     * Returns the rendered loader source.
     *
     * @return string Loader class source.
     */
    public function __toString(): string
    {
        return $this->loader_output;
    }

    /**
     * Scaffolds the front controller when the project has none.
     *
     * Copies the shipped `templates/index.php` into the project root and
     * never overwrites an existing file.
     *
     * @param string $root Project root.
     * @return bool True when the front controller was created.
     */
    public static function ensureFrontController(string $root): bool
    {
        $index = sprintf('%s/index.php', rtrim($root, '/'));
        if (Fs::isFile($index)) {
            return false;
        }

        $template = Templates::path('index.php');
        if ($template === null) {
            return false;
        }

        Fs::touchWrite($index, (string) Fs::cat($template));

        return true;
    }

    /**
     * Writes the application config files the project is missing.
     *
     * Every `cfg/*.ini` is generated from its own template (`templates/cfg/*.sgf`,
     * resolving project-first), so a project's copies are independent of the
     * framework's `cfg/*.ini` defaults and can diverge from them. `app.ini` is
     * rendered by `app:make` from the same template directory; here it is only
     * backfilled when an application name is resolvable. Existing files are
     * never overwritten.
     *
     * Templates receive the application name (`app`) and whether Strukt Ar is
     * installed (`bundle_db`), so a section that depends on an optional package
     * — the `[bundle-db]` command group — is only written when the package that
     * provides it is present.
     *
     * @param string $root Project root.
     * @param string|null $app Application name, used when `app.ini` is backfilled.
     * @return list<string> Basenames of the created config files.
     */
    public static function ensureConfigs(string $root, ?string $app = null): array
    {
        $target = sprintf('%s/cfg', rtrim($root, '/'));
        $templateDir = Templates::path('cfg');
        if ($templateDir === null) {
            return [];
        }

        $app = $app ?? ConfigLoader::app();
        $bundleDb = class_exists(\Strukt\Ar\Model::class);

        $created = [];
        foreach (glob(sprintf('%s/*.sgf', $templateDir)) ?: [] as $source) {
            $file = sprintf('%s.ini', pathinfo($source, PATHINFO_FILENAME));

            if ($file === 'app.ini' && $app === '') {
                continue;
            }

            $destination = sprintf('%s/%s', $target, $file);
            if (Fs::isFile($destination)) {
                continue;
            }

            $contents = Templator::create((string) Fs::cat($source), [
                'app' => $app,
                'bundle_db' => $bundleDb,
            ]);
            if (Fs::touchWrite($destination, $contents)) {
                $created[] = $file;
            }
        }

        sort($created);

        return $created;
    }

    /**
     * Writes the facet provider when it is missing.
     *
     * The `facet` provider owns the `provider.core` event that `core()` and
     * `Core::get()` resolve facets through. It is scaffolded alongside the
     * loader because it is a framework-owned application provider.
     *
     * @param string $root Project root.
     * @return bool True when the provider was created.
     */
    public static function ensureFacetProvider(string $root): bool
    {
        $path = Templates::install('lib/App/Provider/Facet.php', [], $root);

        if (Fs::isFile($path)) {
            return false;
        }

        $template = Templates::path('lib/App/Provider/Facet.sgf');
        if ($template === null) {
            return false;
        }

        Fs::mkdir(dirname($path));
        Fs::touchWrite($path, (string) Fs::cat($template));

        return true;
    }
}
