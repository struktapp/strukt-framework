<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Raise;

/**
 * Resolves application module functionality by alias.
 *
 * A controller or router extends this class to reach any aliased facet through
 * the `provider.core` event, without importing the target namespace directly.
 *
 * ```php
 * final class User extends Controller
 * {
 *     public function show()
 *     {
 *         return $this->get('au.ctr.User'); // resolves the aliased controller
 *     }
 * }
 * ```
 */
class Core
{
    /**
     * Resolves a class by its module alias.
     *
     * The alias uses the `<module>.<facet>.<class>` form, for example
     * `au.ctr.User`.
     *
     * @param string $alias_ns Module alias.
     * @param list<mixed>|null $args Constructor arguments, or null.
     * @return object Resolved object.
     *
     * @throws Raise When the core provider is not registered.
     */
    protected function get(string $alias_ns, ?array $args = null): object
    {
        $provider = event('provider.core');
        if ($provider === null) {
            throw new Raise('The [provider.core] provider is not registered.');
        }

        return $provider->apply($alias_ns, $args)->exec();
    }
}
