<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Fs\Fs;

/**
 * Scaffolds the application `.env` file.
 *
 * Writes the framework default key/value pairs when no `.env` exists yet and
 * never overwrites an existing file, so project-specific overrides survive:
 *
 * ```php
 * Environment::ensure($root); // creates <root>/.env on first run
 * ```
 */
final class Environment
{
    /** @var array<string, string> Default environment keys and values. */
    public const KEYS = [
        'serve_host' => '127.0.0.1',
        'serve_port' => '8080',
        'serve_ctx' => '.',
        'is_dev' => 'true',
        'rel_appsrc' => 'app/src',
        'rel_app_lib' => 'lib/App',
        'rel_mod_ini' => 'cfg/module.ini',
    ];

    /**
     * Creates `<root>/.env` with the default values when it is missing.
     *
     * @param string $root Project root.
     * @return bool True when the file was created, false when it already
     *              exists.
     */
    public static function ensure(string $root): bool
    {
        $file = self::file($root);
        if (Fs::isFile($file)) {
            return false;
        }

        $lines = [];
        foreach (self::KEYS as $key => $value) {
            $lines[] = sprintf('%s = %s', $key, $value);
        }

        Fs::touchWrite($file, implode("\n", $lines) . "\n");

        return true;
    }

    /**
     * Absolute path of the project environment file.
     */
    public static function file(string $root): string
    {
        return sprintf('%s/.env', rtrim($root, '/'));
    }
}