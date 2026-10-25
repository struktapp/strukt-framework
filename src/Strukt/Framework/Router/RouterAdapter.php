<?php

declare(strict_types=1);

namespace Strukt\Framework\Router;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Framework\Contract\Kernel;
use Strukt\Ref;
use Strukt\Router\Router;

/**
 * Adapts the concrete {@see \Strukt\Router\Router} to the
 * {@see Kernel} port used by the application.
 *
 * Route verbs, middleware enrichment, and run/handle dispatch are the only
 * place where transport types are touched, so the rest of the framework stays
 * router agnostic.
 */
final class RouterAdapter implements Kernel
{
    public function __construct(private Router $router)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function addRoute(array $config): void
    {
        $action = Ref::create($config['ref.class'])
            ->noMake()
            ->method($config['ref.method'])
            ->getClosure();

        $route = $this->verb(strtoupper((string) ($config['http.method'] ?? 'GET')))($config['route.path'] ?? '/', $action);

        if (!empty($config['route.middlewares'])) {
            $route->middleware(array_values((array) $config['route.middlewares']));
        }

        if (!empty($config['route.perm'])) {
            if ($config['route.perm'] === 'strukt:auth') {
                $route->requiresAuth();
            } else {
                $route->permissions((array) $config['route.perm']);
            }
        }

        if (!empty($config['route.form'])) {
            $route->form((string) $config['route.form']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addMiddleware(object|callable|string ...$definitions): void
    {
        $this->router->middleware(...$definitions);
    }

    /**
     * {@inheritDoc}
     */
    public function namedMiddleware(string $alias, object|callable|string $definition): void
    {
        $this->router->named($alias, $definition);
    }

    /**
     * {@inheritDoc}
     */
    public function run(): void
    {
        $this->router->kernel()->send();
    }

    /**
     * {@inheritDoc}
     */
    public function handle(?RequestInterface $request = null): ResponseInterface
    {
        return $this->router->kernel()->handle($request);
    }

    /**
     * Returns the registrar for one HTTP verb.
     *
     * @param string $verb Upper case HTTP method.
     * @return callable(string, callable): \Strukt\Router\Route Route registrar.
     */
    private function verb(string $verb): callable
    {
        return match ($verb) {
            'GET' => $this->router->get(...),
            'POST' => $this->router->post(...),
            'PUT' => $this->router->put(...),
            'PATCH' => $this->router->patch(...),
            'DELETE' => $this->router->delete(...),
            'OPTIONS' => $this->router->options(...),
            'HEAD' => $this->router->head(...),
            default => $this->router->any(...),
        };
    }
}