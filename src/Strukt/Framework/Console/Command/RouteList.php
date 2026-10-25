<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Framework\Application;
use Strukt\Framework\Autoload;

/**
 * @command route:ls
 * @description List registered routes
 * @usage route:ls [<filter>]
 * @argument filter
 * @description Optional: criteria for filtering routes
 */
final class RouteList implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        Autoload::register();

        $loader = class_exists(\App\Loader::class) ? new \App\Loader() : null;
        $app = $loader !== null && method_exists($loader, 'getApp')
            ? $loader->getApp()
            : Application::make();

        $app->init();

        $routes = function_exists('router') ? router()->collection()->all() : [];
        if ($routes === []) {
            echo "No routes registered.\n";

            return 0;
        }

        $filter = (string) ($arguments['filter'] ?? '');

        foreach ($routes as $route) {
            $pattern = $route->pattern();
            if ($filter !== '' && !str_contains($pattern, $filter)) {
                continue;
            }

            printf("%-8s %s\n", implode('|', $route->methods()), $pattern);
        }

        return 0;
    }
}