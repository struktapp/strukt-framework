# Routing

Routing in `strukt/framework` is annotation driven. A **module** groups
**routers**, and the public methods of a router annotated with `@Method` become
routes on the underlying `strukt/router` instance.

## Modules

A module is a namespace that groups application facets. Extend
`Strukt\Framework\Contract\Module` and give it an alias:

```php
namespace Payroll\AuthModule;

use Strukt\Framework\Contract\Module;

final class PayrollAuthModule extends Module
{
    protected string $alias = 'au';
}
```

The module's directory (where the class file lives) is scanned by the
[`Registrar`](../src/Strukt/Framework/Module/Registrar.php). Facet folders are
configured in [`cfg/module.ini`](../cfg/module.ini):

```ini
[folders]
ctr = Controller
rtr = Router
frm = Form
tes = Tests
```

For every file beneath one of those folders, the registrar records
`nr.<alias>.<facet>.<name>` in the module store, and collects the `Router`
classes under `nr.routes.<alias>`. Registration is idempotent per module
namespace.

Register a module on the application:

```php
$app = Application::make();
$app->register(new PayrollAuthModule());
```

## Routers

A router extends `Strukt\Framework\Contract\Router` and declares one public
method per route:

```php
namespace Payroll\AuthModule\Router;

use Strukt\Framework\Contract\Router;

/**
 * @Prefix(/api/v1)
 */
final class Index extends Router
{
    /**
     * @Route(/)
     * @Method(GET)
     */
    public function welcome(): string
    {
        return 'Strukt works!';
    }

    /**
     * @Route(/hello/{name:alpha})
     * @Method(GET)
     */
    public function hello(string $name): string
    {
        return sprintf('Hello %s!', $name);
    }
}
```

Route annotations are read by
[`Injectable\Router`](../src/Strukt/Framework/Injectable/Router.php). **A method
is a route only when it carries `@Method`.** The class is instantiated without
running its constructor (via `Strukt\Ref`), so a router needs no constructor.

> **One annotation per line.** The annotation parser reads a single annotation
> per docblock line. Write `@Route(...)` and `@Method(...)` on separate lines.

### Class-level prefix

A router may declare `@Prefix(/path)` on its class docblock. The prefix is
prepended to every method route on that class, matching the join semantics of
`Strukt\Router\Router::group()`. Omit a leading `/` and `route:ls` will show the
effective path as registered with the kernel.

### Folder-level prefix

Drop `Prefix.php` in a router folder (e.g.
`app/src/<App>/<Module>/Router/Prefix.php`) with a class that provides the
prefix:

```php
<?php

namespace Payroll\AuthModule\Router;

final class Prefix
{
    public function prefix(): string
    {
        return '/api/v1';
    }
}
```

The loader checks `prefix()`, then a public `$prefix` property, then
`getPrefix()`. A class-level `@Prefix` takes precedence over `Prefix.php`. Either
form can be mixed with module/facet organization; use folder-level prefixes when
many routers share the same base path.


## Route annotations

| Annotation | Value | Effect |
| --- | --- | --- |
| `@Prefix(PATH)` | `/api/v1` | Class-level: prepends the prefix to all routes in this router. |
| `@Method(VERB)` | `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`, `HEAD`, or anything else | HTTP verb. Unknown verbs fall back to `any`. |
| `@Route(PATH)` | `/user/{id}`, `/hello/{name:alpha}` | URL path with optional typed placeholders. |
| `@Form(Class)` | A form class name | Resolves and validates a form for the action (see below). |
| `@Middleware(name)` | One alias | Attaches a named middleware to the route. |
| `@Middlewares(a, b)` | Comma list | Attaches several middleware aliases. |
| `@Permission(perm)` | A permission string | Requires the permission on the route. |
| `@Auth()` | — | Shorthand for `@Permission(strukt:auth)`; calls `requiresAuth()`. |

`@Middleware` accepts either a single value or a comma list; when both
`@Middleware` and `@Middlewares` are present the singular form wins.

The parser flattens each route into a configuration consumed by the
[`RouterAdapter`](../src/Strukt/Framework/Router/RouterAdapter.php):

| Key | Source |
| --- | --- |
| `http.method` | `@Method` |
| `route.path` | `@Route` (defaults to `/`) |
| `route.perm` | `@Permission`, or `strukt:auth` for `@Auth` |
| `route.form` | `@Form` |
| `route.middlewares` | `@Middleware` / `@Middlewares` |
| `ref.class` / `ref.method` | The declaring class and method |

## Dispatch and parameter resolution

`Application::init()` registers every route with the `Kernel`. On a match, the
router kernel invokes the action through
`Strukt\Router\Invoker`, resolving each parameter in order:

1. a matched route parameter of the same name (`{id}`, `{name:alpha}`),
2. the request, response, session, or auth gate by interface,
3. a validated route form by `Strukt\Contract\FormInterface`,
4. a named binding, a declared default, or a nullable parameter,
5. otherwise a resolution error is raised.

Supported parameter interfaces:

```php
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\AuthInterface;
use Strukt\Contract\FormInterface;

public function store(RequestInterface $request, FormInterface $form, AuthInterface $auth)
{
    // ...
}
```

## Route forms

Declaring `@Form(LoginForm)` on a route tells the invoker to build the form,
submit the request body, and validate it before the action runs. A validation
failure throws `ValidationException`, which the router kernel maps to a `422`
JSON response:

```json
{ "errors": { "email": ["Email is required."] } }
```

The action can then type its parameter as the form interface to receive the
validated form. See [forms-and-validation.md](forms-and-validation.md).

## Middleware

Middleware is any class implementing `Strukt\Contract\MiddlewareInterface` with
a `process()` method:

```php
namespace App\Middleware;

use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;

/**
 * @Name(audit)
 */
final class Audit implements MiddlewareInterface
{
    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        // ... before
        $response = $next($request, $response);
        // ... after

        return $response;
    }
}
```

Middleware reaches the pipeline in two ways:

1. **Global** — configured in `cfg/app.ini` (`middlewares[] = ...`) or discovered
   under `lib/App/Middleware` (see [extensions.md](extensions.md)). Global
   middleware runs on every request; it is registered on the kernel and, when it
   declares `@Name`, also registered as a named middleware.
2. **Per route** — referenced by alias from `@Middleware(name)` /
   `@Middlewares(...)`, or added directly in code via the `Router` fluent API.

The core package ships these middleware (`Strukt\Bundle\Core::getSettings`):

| Alias | Class |
| --- | --- |
| `sess` | `Strukt\Router\Middleware\Session` |
| `authz` | `Strukt\Router\Middleware\Authorization` |
| `auth` | `Strukt\Router\Middleware\Authentication` |
| `valid` | `Strukt\Framework\Middleware\Validator` |
| `jwt` | `Strukt\Jwt` |
| `cors` | `App\Middleware\Cors` |

`valid` is a compatibility no-op: route forms are validated by the invoker, so
the middleware only forwards the request while remaining addressable.

## The kernel port

The framework depends on `Strukt\Framework\Contract\Kernel`:

```php
interface Kernel
{
    public function addRoute(array $config): void;
    public function addMiddleware(object|callable|string ...$definitions): void;
    public function namedMiddleware(string $alias, object|callable|string $definition): void;
    public function run(): void;
    public function handle(?RequestInterface $request = null): ResponseInterface;
}
```

`RouterAdapter` implements it over `Strukt\Router\Router`. To use a different
transport, implement the port and pass an `Application` a kernel, or wrap your
router. `Application::make($router)` wraps a `Strukt\Router\Router` you supply,
which is how tests drive routes against an isolated router instance.

## Inspecting routes

```bash
vendor/bin/strukt route:ls          # all routes
vendor/bin/strukt route:ls /user    # filter by path substring
```

`route:ls` boots the application (preferring `App\Loader` when present) and
prints the method list and pattern of every registered route.

## Scaffolding routes

`route:make` generates a router or adds a route method to an existing one. It is
hybrid: supply the values as arguments and options, or omit them and answer the
prompts on an interactive terminal.

```bash
vendor/bin/strukt route:make au User /user/all
vendor/bin/strukt route:make au User '/user/{id:int}' --method=POST --permission=user-all
vendor/bin/strukt route:make au User /user/all --function=listAll
```

| Argument / option | Description |
| --- | --- |
| `<module>` | Module alias from the registered modules, such as `au` |
| `<name>` | Router class name; a trailing `Router` is stripped |
| `<route>` | Route path; a leading `/` is added when missing |
| `--method=` | HTTP verb, one of `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`, `HEAD` (default `GET`) |
| `--permission=` | Optional `@Permission(...)` value |
| `--function=` | Action method name; otherwise derived from the route |

The command writes `app/src/<App>/<Module>/<rtr>/<Name>.php`, where `<rtr>` is
the router folder from `cfg/module.ini`. When the file already exists the new
method is inserted before the class closing brace, so hand-written methods are
kept. Typed placeholders in the route become typed parameters:

```php
/**
 * @Method(POST)
 * @Route(/user/{id:int})
 */
public function user(RequestInterface $request, int $id)
{
    //
}
```

When an argument is omitted and the terminal is not interactive, the command
fails instead of blocking, so it is safe to script.

## Facet resolution with `core()`

Facets are indexed by alias, so one facet can reach another without importing its
namespace:

```php
// resolve the controller indexed at nr.au.ctr.User
$user = core('au.ctr.User', ['request' => request()]);
```

`core()` (helper) and `Core::get()` (base class) both go through the
`provider.core` event, which the `facet` provider owns. Inside a controller or
router that extends `Strukt\Framework\Contract\Core`, call `$this->get(...)`.

Both resolution paths need a booted application: `facet` must be registered and
the `nr.*` alias map populated. The `shell:exec` command therefore boots the
application before opening the session, so `core('au.ctr.User')` works in the
REPL the same way it does at runtime.
