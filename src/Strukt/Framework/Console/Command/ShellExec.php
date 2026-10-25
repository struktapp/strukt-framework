<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Framework\Application;
use Strukt\Framework\Autoload;
use Strukt\Framework\Core as Framework;

/**
 * @command shell:exec
 * @description Start an interactive PsySH session bound to the framework core
 */
final class ShellExec implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        if (!class_exists(\Psy\Shell::class)) {
            throw new \Strukt\Raise('PsySH is not installed. Run: composer require psy/psysh --dev');
        }

        Autoload::register();

        $loader = class_exists(\App\Loader::class) ? new \App\Loader() : null;
        $app = $loader?->getApp() ?? Application::make();
        $app->init();

        $core = new class extends Framework {
            public function get(string $alias_ns, ?array $args = null): object
            {
                return parent::get($alias_ns, $args);
            }
        };

        $shell = new \Psy\Shell();
        $shell->setScopeVariables(['core' => $core]);
        $shell->run();

        return 0;
    }
}