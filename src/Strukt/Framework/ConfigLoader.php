<?php

declare(strict_types=1);

namespace Strukt\Framework;

/**
 * Loads a project's own INI configuration sections.
 *
 * The shared `config()` helper only reads the INI files shipped by installed
 * packages. A project owns editable copies under `<root>/cfg/`, which are
 * merged over those defaults here so an application can change any section
 * without touching vendor code.
 */
final class ConfigLoader
{
    /**
     * Merges `<root>/cfg/*.ini` into the shared config registry.
     *
     * Keys the project does not override are preserved, so sections supplied by
     * a dependency (for example `app.name`) survive. Safe to call repeatedly:
     * merging the same values again is idempotent.
     *
     * @param string $root Project root.
     * @return list<string> Section names that were loaded.
     */
    public static function load(string $root): array
    {
        $directory = sprintf('%s/cfg', rtrim($root, '/\\'));
        if (! is_dir($directory)) {
            return [];
        }

        $loaded = [];
        foreach (glob(sprintf('%s/*.ini', $directory)) ?: [] as $file) {
            $section = pathinfo($file, PATHINFO_FILENAME);

            $parsed = parse_ini_file($file, true, INI_SCANNER_TYPED);
            if ($parsed === false) {
                continue;
            }

            $current = config($section);
            config($section, is_array($current) ? array_replace_recursive($current, $parsed) : $parsed);

            $loaded[] = $section;
        }

        return $loaded;
    }

    /**
     * Resolves the project root from the environment, falling back to cwd.
     *
     * @return string Project root without a trailing separator.
     */
    public static function root(): string
    {
        $root = \Strukt\Env::has('root_dir') ? (string) \Strukt\Env::get('root_dir') : getcwd();

        return rtrim((string) $root, '/\\');
    }

    /**
     * Resolves the application name used when rendering config templates.
     *
     * Prefers the configured `app.name` (which `strukt/commons` ships) and falls
     * back to `app-name` in the project's `cfg/app.ini`, so backfilling works
     * before the config registry has been populated.
     *
     * @return string Application name, or an empty string when unknown.
     */
    public static function app(): string
    {
        $name = (string) (config('app.name') ?? '');
        if ($name !== '') {
            return $name;
        }

        $file = sprintf('%s/cfg/app.ini', self::root());
        if (is_file($file)) {
            $parsed = parse_ini_file($file, true, INI_SCANNER_TYPED);
            if (is_array($parsed) && isset($parsed['app-name'])) {
                return (string) $parsed['app-name'];
            }
        }

        return '';
    }
}
