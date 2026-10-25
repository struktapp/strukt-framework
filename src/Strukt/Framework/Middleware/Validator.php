<?php

declare(strict_types=1);

namespace Strukt\Framework\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;

/**
 * Validation middleware kept for annotation compatibility.
 *
 * In the modern router, a route's declared form is resolved and validated by
 * the router invoker whenever an action parameter is typed as a
 * {@see \Strukt\Contract\FormInterface}; a failure raises a validation error
 * that the kernel maps to a `422` response. This middleware therefore only
 * forwards the request, but remains addressable as `valid` so annotated routes
 * continue to resolve:
 *
 * ```php
 * /** @Method(POST) @Route(/login) @Middleware(valid) @Form(LoginForm) *\/
 * ```
 */
final class Validator implements MiddlewareInterface
{
    /**
     * Forwards the request to the next middleware.
     *
     * @param RequestInterface $request Incoming request.
     * @param ResponseInterface $response Response under construction.
     * @param callable $next Invokes the remaining pipeline.
     * @return ResponseInterface Response for the caller.
     */
    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        return $next($request, $response);
    }
}
