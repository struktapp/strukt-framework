<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;

/**
 * The minimal HTTP surface an application kernel must expose.
 *
 * Depending on this port instead of a concrete router keeps the kernel
 * swappable and the application free of transport details. Concrete
 * implementations wrap a real router and translate route configurations.
 */
interface Kernel
{
    /**
     * Registers one parsed route configuration.
     *
     * @param array<string, mixed> $config Flattened route configuration.
     * @return void
     */
    public function addRoute(array $config): void;

    /**
     * Queues middleware definitions on the global pipeline.
     *
     * @param object|callable|string ...$definitions Middleware definitions.
     * @return void
     */
    public function addMiddleware(object|callable|string ...$definitions): void;

    /**
     * Associates an alias with a middleware definition.
     *
     * @param string $alias Addressable middleware name.
     * @param object|callable|string $definition Middleware definition.
     * @return void
     */
    public function namedMiddleware(string $alias, object|callable|string $definition): void;

    /**
     * Dispatches the incoming request and sends the response.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Dispatches one request and returns the response.
     *
     * @param RequestInterface|null $request Request to handle, or the globals.
     * @return ResponseInterface Resulting response.
     */
    public function handle(?RequestInterface $request = null): ResponseInterface;
}