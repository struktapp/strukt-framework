<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Framework\Configuration;

/**
 * @command sys:ls
 * @description List facets - providers|middlewares (system)
 * @usage sys:ls [<type>]
 * @argument type
 * @description Optional: (providers|middlewares)
 * @enum providers,middlewares
 */
final class SysList implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $type = (string) ($arguments['type'] ?? '');

        $configuration = new Configuration();
        $providers = $configuration->get('providers');
        $middlewares = $configuration->get('middlewares');

        $showMiddlewares = $type === 'middlewares' || $type === '';
        $showProviders = $type === 'providers' || $type === '';

        if ($showMiddlewares) {
            echo "\nMiddlewares\n";
            foreach ($middlewares as $middleware) {
                printf(" %s\n", $middleware);
            }
        }

        if ($showProviders) {
            echo "\nProviders\n";
            foreach ($providers as $provider) {
                printf(" %s\n", $provider);
            }
        }

        return 0;
    }
}