<?php

declare(strict_types=1);

namespace Strukt\Framework\Console;

use Strukt\Env;
use Strukt\Raise;

/**
 * Availability and path defaults shared by the Strukt Ar bridge commands.
 *
 * The Ar commands are only meaningful inside an application, so their targets
 * default to the application source tree discovered under `rel_appsrc` instead
 * of Ar's standalone `build/Models` and `App\Model` defaults.
 */
trait ArCommandSupport
{
    /**
     * Reports whether Strukt Ar is installed.
     *
     * @return bool True when the Ar model base class is autoloadable.
     */
    public static function isAvailable(): bool
    {
        return class_exists(\Strukt\Ar\Model::class);
    }

    /**
     * Resolves the model namespace, defaulting to the application root namespace.
     *
     * @param string|null $namespace Provided namespace.
     * @return string Resolved namespace.
     */
    protected static function resolveNamespace(?string $namespace): string
    {
        $namespace = trim((string) $namespace, '\\');
        if ($namespace !== '') {
            return $namespace;
        }

        $namespaces = self::appNamespaces();
        if ($namespaces === []) {
            throw new Raise(
                'Unable to determine the application namespace. '
                . 'Pass --namespace (and optionally --dir).'
            );
        }

        $app = (string) config('app.name');
        foreach ($namespaces as $candidate) {
            if ($app !== '' && strcasecmp($candidate, $app) === 0) {
                return $candidate;
            }
        }

        return $namespaces[0];
    }

    /**
     * Resolves the model directory, defaulting under the application source.
     *
     * @param string|null $dir Provided directory.
     * @param string $namespace Resolved namespace.
     * @return string Resolved directory.
     */
    protected static function resolveDir(?string $dir, string $namespace): string
    {
        $dir = trim((string) $dir);
        if ($dir !== '') {
            return $dir;
        }

        return sprintf(
            '%s/%s/%s',
            rtrim((string) Env::get('root_dir'), '/'),
            trim((string) Env::get('rel_appsrc'), '/'),
            str_replace('\\', '/', trim($namespace, '\\')),
        );
    }

    /**
     * Lists the first-level application namespaces under the source directory.
     *
     * @return list<string> Candidate namespaces, sorted.
     */
    private static function appNamespaces(): array
    {
        $base = sprintf(
            '%s/%s',
            rtrim((string) Env::get('root_dir'), '/'),
            trim((string) Env::get('rel_appsrc'), '/'),
        );

        $namespaces = array_map('basename', glob($base . '/*', GLOB_ONLYDIR) ?: []);
        sort($namespaces);

        return array_values($namespaces);
    }
}
