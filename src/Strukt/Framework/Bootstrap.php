<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Container\Container;
use Strukt\Framework\Contract\Kernel;
use Strukt\Framework\Traits\InjectablesHelper;

/**
 * Wires the aggregated providers and middlewares onto a kernel.
 *
 * Providers are resolved through the container (when one is given) and
 * registered, and every configured middleware is queued on the kernel
 * pipeline, addressable by its declared alias. The application extendables
 * discovered under `lib/App` are merged in, so a middleware or provider
 * dropped there is wired without configuration.
 */
final class Bootstrap
{
    use InjectablesHelper;

    /**
     * Applies one configuration to the kernel.
     *
     * @param Kernel $kernel Kernel receiving the wiring.
     * @param Configuration|null $configuration Aggregated settings, or null.
     * @param Container|null $container Container resolving providers, or null.
     * @return void
     */
    public static function run(Kernel $kernel, ?Configuration $configuration, ?Container $container = null): void
    {
        $extendables = Extendables::discover();
        $extendables->register();

        $providers = array_values(array_unique(array_merge(
            $configuration?->get('providers') ?? [],
            $extendables->providers(),
        )));

        $middlewares = array_values(array_unique(array_merge(
            $configuration?->get('middlewares') ?? [],
            $extendables->middlewares(),
        )));

        foreach ($providers as $class) {
            $provider = $container?->make($class) ?? new $class();
            $provider->register();
            $provider->boot();
        }

        foreach ($middlewares as $class) {
            $kernel->addMiddleware($class);

            $meta = self::resolveInjectables($class);
            if ($meta !== null && $meta['alias'] !== '') {
                $kernel->namedMiddleware($meta['alias'], $class);
            }
        }
    }
}