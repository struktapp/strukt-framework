<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Container\Container;
use Strukt\Framework\Contract\Kernel;
use Strukt\Framework\Contract\Module;
use Strukt\Framework\Injectable\Router as InjectableRouter;
use Strukt\Framework\Module\Registrar;
use Strukt\Framework\Router\RouterAdapter;
use Strukt\Router\Router;

/**
 * The application kernel orchestrator.
 *
 * The application delegates its three responsibilities to focused
 * collaborators and only sequences them:
 *
 * - {@see Registrar} indexes registered modules and their facets.
 * - {@see Bootstrap} wires providers and middlewares onto the kernel.
 * - {@see Kernel} (wrapped by {@see RouterAdapter}) receives routes and
 *   dispatches requests.
 *
 * Composition runs through the shared container, which resolves the registrar,
 * configuration, and kernel from their registered services.
 *
 * ```php
 * $app = Application::make();
 * $app->register(new AuthModule());
 * $app->run();
 * ```
 */
final class Application
{
    /**
     * @param Kernel $kernel Router port that receives routes and requests.
     * @param Registrar $registrar Module discovery and indexing.
     * @param Configuration|null $configuration Optional aggregated settings.
     * @param Container|null $container Optional container resolving providers.
     */
    public function __construct(
        private Kernel $kernel,
        private Registrar $registrar,
        private ?Configuration $configuration = null,
        private ?Container $container = null,
    ) {
    }

    /**
     * Builds an application on the shared router and container services.
     *
     * @param Router|null $router Router to wrap, or the global instance.
     * @param Configuration|null $configuration Optional aggregated settings.
     * @return static New application.
     */
    public static function make(?Router $router = null, ?Configuration $configuration = null): static
    {
        Autoload::register();
        ConfigLoader::load(ConfigLoader::root());
        Extendables::discover()->register();

        $container = container();

        $kernel = $router instanceof Router
            ? new RouterAdapter($router)
            : $container->get(Kernel::class);

        $config = $configuration
            ?? (class_exists(\App\Injectable::class) ? $container->get(Configuration::class) : null);

        return new static($kernel, $container->get(Registrar::class), $config, $container);
    }

    /**
     * Registers a module and indexes its facets by alias.
     *
     * @param Module $module Module to register.
     * @return void
     */
    public function register(Module $module): void
    {
        $this->registrar->register($module);
    }

    /**
     * Registers providers, middlewares, and routes with the kernel.
     *
     * @return static This application.
     */
    public function init(): static
    {
        reg('nr.modules', $this->registrar->modules());

        Bootstrap::run($this->kernel, $this->configuration, $this->container);

        foreach ($this->registrar->aliases() as $alias) {
            foreach ($this->registrar->routes($alias) as $class) {
                foreach ((new InjectableRouter(new \ReflectionClass($class)))->getConfigs() ?? [] as $config) {
                    $config['module'] = $alias;
                    $this->kernel->addRoute($config);
                }
            }
        }

        return $this;
    }

    /**
     * Dispatches the incoming request.
     *
     * @return void
     */
    public function run(): void
    {
        $this->init();
        $this->kernel->run();
    }

    /**
     * Initializes and dispatches one request, returning its response.
     *
     * @param RequestInterface|null $request Request to handle, or the globals.
     * @return ResponseInterface Resulting response.
     */
    public function handle(?RequestInterface $request = null): ResponseInterface
    {
        $this->init();

        return $this->kernel->handle($request);
    }

    /**
     * Returns the registered modules keyed by alias.
     *
     * @return array<string, string> Module namespaces keyed by alias.
     */
    public function modules(): array
    {
        return $this->registrar->modules();
    }
}