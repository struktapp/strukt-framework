<?php

declare(strict_types=1);

namespace Strukt\Bundle;

/**
 * Resolves the framework bundles configured for an application.
 *
 * Bundle names come from the `repo` configuration and map to either a
 * `Strukt\Bundle\<Name>` class or, when the configured value is `Extender`, to
 * an `App\<Name>` class.
 */
final class Bundles
{
    /**
     * Lists every configured bundle and its class.
     *
     * @return array<string, string> Bundle classes keyed by name.
     */
    public static function available(): array
    {
        $repos = config('repo');
        if (!is_array($repos)) {
            return [];
        }

        $bundles = [];
        foreach ($repos as $name => $repo) {
            $bundles[$name] = $repo === 'Extender'
                ? sprintf('App\%s', $repo)
                : sprintf('Strukt\Bundle\%s', $repo);
        }

        return $bundles;
    }

    /**
     * Lists the bundles of a given status.
     *
     * @param string $type Either `installed` or `published`.
     * @return list<string> Bundle names matching the status.
     */
    public static function list(string $type): array
    {
        $bundles = [];
        foreach (self::available() as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }

            if ($type === 'installed') {
                $bundles[] = $name;
            }

            if ($type === 'published' && container()->make($class)->isPublished()) {
                $bundles[] = $name;
            }
        }

        return $bundles;
    }
}
