<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Fs\Fs;
use Strukt\Templator;

/**
 * Resolves the scaffolding templates used by the generator commands.
 *
 * Projects own editable copies under `<root>/templates/`; when a file is not
 * there the framework's `templates/` directory is used as the fallback. This
 * mirrors the config layering so `app:make` and the `*:make` commands generate
 * code from the project's templates once it has customised them.
 */
final class Templates
{
    /**
     * Returns the framework's template directory.
     *
     * @return string Absolute path to the shipped templates.
     */
    public static function defaults(): string
    {
        return sprintf('%s/templates', dirname(__DIR__, 3));
    }

    /**
     * Returns the project's template directory.
     *
     * @param string|null $root Project root, or the configured root when null.
     * @return string Absolute path to the project templates.
     */
    public static function dir(?string $root = null): string
    {
        return sprintf('%s/templates', rtrim($root ?? ConfigLoader::root(), '/\\'));
    }

    /**
     * Resolves one template, preferring the project copy.
     *
     * @param string $relative Template path relative to a templates directory.
     * @return string|null Absolute path, or null when neither copy exists.
     */
    public static function path(string $relative): ?string
    {
        $relative = ltrim($relative, '/\\');

        foreach ([self::dir(), self::defaults()] as $directory) {
            $path = sprintf('%s/%s', $directory, $relative);
            if (Fs::isFile($path) || Fs::isDir($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Maps a template-relative path onto its install destination.
     *
     * A template mirrors the path its output is written to, so the same
     * relative path resolves the file on disk. The `lib/App` and `app/src`
     * prefixes resolve against `rel_app_lib` and `rel_appsrc` respectively;
     * anything else lands relative to the project root. Placeholders such as
     * `{{class_name}}` are expanded using `$data`.
     *
     * @param string $relative Install path relative to a templates directory.
     * @param array<string, string> $data Placeholder values.
     * @param string|null $root Project root, or the configured root when null.
     * @return string Absolute install path.
     */
    public static function install(string $relative, array $data = [], ?string $root = null): string
    {
        $root = rtrim($root ?? ConfigLoader::root(), '/\\');
        $relative = Templator::create(ltrim($relative, '/\\'), $data);

        if (str_starts_with($relative, 'lib/App/') || str_starts_with($relative, 'app/src/')) {
            [$prefix, $configured, $default] = str_starts_with($relative, 'lib/App/')
                ? ['lib/App/', \Strukt\Env::has('rel_app_lib') ? (string) \Strukt\Env::get('rel_app_lib') : 'lib/App', 'lib/App']
                : ['app/src/', \Strukt\Env::has('rel_appsrc') ? (string) \Strukt\Env::get('rel_appsrc') : 'app/src', 'app/src'];

            $base = trim($configured, '/\\') === '' ? $default : $configured;
            $base = self::isAbsolute($base) ? rtrim($base, '/\\') : sprintf('%s/%s', $root, trim($base, '/\\'));

            return sprintf('%s/%s', $base, substr($relative, strlen($prefix)));
        }

        return sprintf('%s/%s', $root, $relative);
    }

    /**
     * Reports whether a path is absolute.
     *
     * @param string $path Path to test.
     * @return bool True for absolute paths.
     */
    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    /**
     * Copies the framework templates the project is missing.
     *
     * Existing project files are never overwritten, so local customisations
     * survive. `app:make` calls this before rendering so a fresh project is
     * generated from its own copies.
     *
     * @param string $root Project root.
     * @return list<string> Relative paths of the created template files.
     */
    public static function ensure(string $root): array
    {
        $source = self::defaults();
        if (! Fs::isDir($source)) {
            return [];
        }

        $target = self::dir($root);
        $created = [];
        foreach (Fs::lsr($source) as $file) {
            $relative = substr($file, strlen($source) + 1);

            $destination = sprintf('%s/%s', $target, $relative);
            if (Fs::isFile($destination)) {
                continue;
            }

            if (Fs::touchWrite($destination, (string) Fs::cat($file))) {
                $created[] = $relative;
            }
        }

        sort($created);

        return $created;
    }
}
