<?php

declare(strict_types=1);

namespace Strukt\Bundle;

use Strukt\Framework\Contract\Bundle;

/**
 * The built-in framework bundle.
 *
 * It publishes the framework middlewares, providers, and console commands that
 * ship with the bundle, filtered against what is actually installed.
 */
final class Core implements Bundle
{
    /** @var array<string, mixed> Bundle manifest. */
    private array $manifest;

    public function __construct()
    {
        $this->manifest = [
            'cmd_name' => 'Strukt',
            'bundle' => 'core',
            'files' => null,
        ];
    }

    public function getName(): string
    {
        return $this->manifest['bundle'];
    }

    public function getCmdName(): string
    {
        return $this->manifest['cmd_name'];
    }

    public function getFiles(): ?array
    {
        return $this->manifest['files'];
    }

    public function getModules(): ?array
    {
        return null;
    }

    public function isPublished(): bool
    {
        return true;
    }

    public function getRequirements(): ?array
    {
        return null;
    }

    public function preInstall(): void
    {
    }

    public function postInstall(): void
    {
    }

    /**
     * Returns the settings contributed for one application type.
     *
     * @param string $type Application type, such as `App:Idx` or `App:Cli`.
     * @return array<string, mixed> Settings for the application type.
     */
    public function getSettings(string $type): array
    {
        $middlewares = $this->keep([
            'sess' => \Strukt\Router\Middleware\Session::class,
            'authz' => \Strukt\Router\Middleware\Authorization::class,
            'auth' => \Strukt\Router\Middleware\Authentication::class,
            'valid' => \Strukt\Framework\Middleware\Validator::class,
            'jwt' => \Strukt\Jwt::class,
            'cors' => \App\Middleware\Cors::class,
        ]);

        $providers = $this->keep([
            'facet' => \App\Provider\Facet::class,
        ]);

        $settings = [
            'App:Idx' => [
                'providers' => $providers,
                'middlewares' => $middlewares,
            ],
            'App:Cli' => [
                'providers' => $providers,
                'middlewares' => $this->keep([
                    \Strukt\Router\Middleware\Authentication::class,
                    \Strukt\Framework\Middleware\Validator::class,
                ]),
                'commands' => $this->commands(),
            ],
        ];

        return $settings[$type] ?? [];
    }

    /**
     * Filters a class list down to the classes that exist.
     *
     * @param array<string, string> $classes Classes keyed by alias or index.
     * @return array<string, string> Existing classes.
     */
    private function keep(array $classes): array
    {
        return array_filter($classes, static fn (string $class): bool => class_exists($class));
    }

    /**
     * Lists the console commands shipped by the bundle.
     *
     * @return list<class-string> Existing command classes.
     */
    private function commands(): array
    {
        $commands = [
            \Strukt\Framework\Console\Command\AppMake::class,
            \Strukt\Framework\Console\Command\AppReload::class,
            \Strukt\Framework\Console\Command\AppExec::class,
            \Strukt\Framework\Console\Command\MiddlewareMake::class,
            \Strukt\Framework\Console\Command\ProviderMake::class,
            \Strukt\Framework\Console\Command\ValidatorMake::class,
            \Strukt\Framework\Console\Command\RouteList::class,
            \Strukt\Framework\Console\Command\ModuleMake::class,
            \Strukt\Framework\Console\Command\ShellExec::class,
            \Strukt\Framework\Console\Command\SysList::class,
            \Strukt\Framework\Console\Command\CacheClear::class,
            \Strukt\Framework\Console\Command\CacheMake::class,
            \Strukt\Framework\Console\Command\CacheReset::class,
            \Strukt\Framework\Console\Command\BundleList::class,
            \Strukt\Framework\Console\Command\BundleInfo::class,
        ];

        return array_values(array_filter($commands, 'class_exists'));
    }
}
