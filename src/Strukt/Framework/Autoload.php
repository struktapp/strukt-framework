<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Env;

/**
 * Registers runtime PSR-4 autoloaders for application namespaces.
 *
 * Maps the injected-feature namespace (`App\`) onto `rel_app_lib` and each
 * first-level application directory under `rel_appsrc` onto its own prefix, so
 * scaffolded applications work without composer autoload edits:
 *
 * ```php
 * Autoload::register(); // App\ -> lib/, Payroll\ -> app/src/Payroll/
 * ```
 */
final class Autoload
{
    /** The registered autoload closure. */
    private static ?\Closure $loader = null;
    /** The root the current autoloader was registered for. */
    private static ?string $root = null;
    /** Whether the autoloader has been registered. */
    private static bool $registered = false;

    /**
     * Registers the application autoloader for the given root.
     *
     * Re-registers when the root changes so separate projects never share a
     * stale map; idempotent for repeated calls on the same root.
     *
     * @param string|null $root Project root; defaults to `Env::root_dir` then
     *                          the current working directory.
     */
    public static function register(?string $root = null): void
    {
        $root = rtrim($root ?? (string) (Env::has('root_dir') ? Env::get('root_dir') : getcwd()), '/\\');

        if (self::$registered && self::$root === $root) {
            return;
        }

        if (self::$registered) {
            self::reset();
        }

        self::$root = $root;

        $map = [];

        $lib = trim((string) (Env::has('rel_app_lib') ? Env::get('rel_app_lib') : 'lib/App'), '/\\');
        if ($lib !== '') {
            $map['App\\'] = sprintf('%s/%s', $root, $lib);
        }

        $appSrc = trim((string) (Env::has('rel_appsrc') ? Env::get('rel_appsrc') : 'app/src'), '/\\');
        $appSrcAbs = sprintf('%s/%s', $root, $appSrc);
        foreach (glob($appSrcAbs . '/*', GLOB_ONLYDIR) ?: [] as $appDir) {
            $map[basename($appDir) . '\\'] = rtrim($appDir, '/\\');
        }

        $loader = static function (string $class) use ($map): void {
            foreach ($map as $prefix => $dir) {
                if (str_starts_with($class, $prefix)) {
                    $file = sprintf(
                        '%s/%s.php',
                        rtrim($dir, '/\\'),
                        str_replace('\\', '/', substr($class, strlen($prefix))),
                    );

                    if (is_file($file)) {
                        require $file;

                        return;
                    }
                }
            }
        };

        spl_autoload_register($loader);

        self::$loader = $loader;
        self::$registered = true;
    }

    /**
     * Drops the registered autoloader (testing).
     */
    public static function reset(): void
    {
        if (self::$registered) {
            spl_autoload_unregister(self::$loader);
            self::$loader = null;
            self::$root = null;
            self::$registered = false;
        }
    }
}