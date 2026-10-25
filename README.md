# Strukt Framework

Annotation-driven application kernel, facades, and console for the `strukt4`
bundle set. This is a modern, full port of `strukt1/strukt-framework`.

```php
use Strukt\Framework\Application;

$app = Application::make();
$app->register(new AuthModule());
$app->run();
```

## Documentation

Full reference documentation lives in [`docs/`](docs/README.md):

- [Architecture](docs/architecture.md) — layers, components, request lifecycle.
- [Getting started](docs/getting-started.md) — scaffold and run an application.
- [Routing](docs/routing.md), [Forms and validation](docs/forms-and-validation.md),
  [Dependency injection](docs/dependency-injection.md),
  [Configuration](docs/configuration.md), [Bundles](docs/bundles.md),
  [Extensions](docs/extensions.md), [Console](docs/console.md),
  [Annotations](docs/annotations.md), [Helpers](docs/helpers.md),
  [Testing](docs/testing.md).
- [Backlog](docs/BACKLOG.md) — outstanding and planned work.

## Requirements

- PHP >= 8.2
- A `strukt4` powered project (base, commons, contract, router, cache, event,
  form, rules, fs, generator, console)

## Installation

```bash
composer require strukt/framework
```

## Quick start

### Console

A `bin/strukt` binary is published to your `vendor/bin`:

```bash
vendor/bin/strukt list
vendor/bin/strukt help module:make
vendor/bin/strukt cache:clear
```

A development-only `bin/xhttp` binary is also published. It dispatches a request
through your application in-process, no web server required:

```bash
vendor/bin/xhttp GET /hello/ada
vendor/bin/xhttp POST /login email=ada@example.com password=secret
```

### Modules and routers

A module groups the application facets beneath one namespace:

```php
final class AuthModule extends \Strukt\Framework\Contract\Module
{
    protected string $alias = 'auth';
}
```

Routers carry method annotations that the `Application` turns into routes on the
`strukt4` router:

```php
final class Login extends \Strukt\Framework\Contract\Router
{
    /**
     * @Route(/auth/login)
     * @Method(POST)
     * @Form(Authentication)
     * @Middleware(sess)
     */
    public function login(\Strukt\Http\Request $request)
    {
        //
    }
}
```

Register a module and dispatch:

```php
$app = Application::make();
$app->register(new AuthModule());
$app->handle();       // returns a ResponseInterface
// or
$app->run();          // sends the response
```

Facets are indexed in the `nr` registry (`nr.<alias>.<facet>.<name>`) and can be
resolved through the `core()` helper:

```php
core('auth.ctr.Login', ['request' => request()]);
```

### Serving the application

`app:make` scaffolds a front controller (`index.php`) and `app:reload` generates
`lib/App/Loader.php`. `app:exec` boots the built-in server with `index.php` as
the router script, so every URL dispatches through the application:

```bash
vendor/bin/strukt app:make payroll
vendor/bin/strukt app:reload
vendor/bin/strukt app:exec
# http://127.0.0.1:8080
```

Application namespaces resolve at runtime through `Strukt\Framework\Autoload`
(`App\` -> `lib/`, and each first-level directory under `app/src` such as
`Payroll\` -> `app/src/Payroll/`), so no composer autoload edits or
`composer dump-autoload` are needed.

`app:reload` (and `app:exec` before the server boots) scaffolds whatever the
project still lacks: a front controller (`index.php`) when none exists, a `.env`
with the `serve_*`, `is_dev`, and `rel_*` defaults, plus the `lib/App/Loader.php`
module loader. Existing files are never overwritten.

## Dependency injection

The framework ships a small PSR-11 container. Resolve its shared instance with
the `container()` helper and ask for a class to autowire it:

```php
use Strukt\Container\Container;
use Strukt\Framework\Contract\Kernel;

$kernel = container()->get(Kernel::class);     // shared binding
$auth = container()->make(\App\Auth\Login::class); // autowired

// register your own bindings
container()->set(\App\Mailer::class, fn (Container $c) => new \App\Mailer(\App\Config::class));
```

The framework wires its own collaborators through the container:

| Identifier | Resolves to |
| --- | --- |
| `Kernel` | `RouterAdapter` over the shared `router()` |
| `Registrar` | module discovery, backed by `ModuleStore` |
| `Configuration` | cached bundle settings |
| `ModuleStore` | the shared `reg()` registry |
| `BundlesRepositoryInterface` | the static `Strukt\Bundle\Bundles` |

Every binding falls back to the previous global accessors, so an existing
application keeps working without changes. To replace a dependency, install an
instance or a factory for one of the above identifiers before resolving it:

```php
container()->instance(\Strukt\Framework\Contract\ModuleStore::class, new \Strukt\Framework\Module\Storage\MemoryStore());
```

Console commands are instantiated through the container-backed
`ContainerCommandFactory`, so a command may declare constructor dependencies.

## Configuration

The framework ships `cfg/*.ini` sections that are merged into the shared
`config` registry (per section, only when the section is not already set):

| Section | Purpose |
| --- | --- |
| `cmd` | Console command groups |
| `module` | Module folders (`folders.ctr`, `folders.rtr`, ...) |
| `cache` | Cache flags (`disable`) |
| `repo` | Installed bundle repositories (`core`) |

Environment values (`.env` or `Env::set`) used by the kernel:

| Key | Default |
| --- | --- |
| `root_dir` | project root |
| `rel_appsrc` | `app/src` |
| `rel_app_lib` | `lib/App` |
| `rel_mod_ini` | `cfg/module.ini` |
| `rel_loader_sgf` | built-in loader template (optional override path) |
| `serve_host` | `127.0.0.1` |
| `serve_port` | `8080` |
| `serve_ctx` | `.` |
| `is_dev` | `true` |

## Console commands

Commands are grouped by the sections configured in `cfg/cmd.ini`:

| Command | Description |
| --- | --- |
| `app:make` | Generate application scaffolding |
| `app:reload` | Generate the application module loader |
| `app:exec` | Run the application on the PHP built-in server |
| `cache:clear` | Clear the cache |
| `cache:make` | Rebuild the application configuration cache |
| `cache:reset` | Reset the configuration cache |
| `middleware:make` | Create middleware scaffolding |
| `provider:make` | Create provider scaffolding |
| `validator:make` | Create validator scaffolding |
| `module:make` | Generate an application module |
| `route:make` | Generate or extend a module router |
| `route:ls` | List registered routes |
| `shell:exec` | Start an interactive PsySH session |
| `sys:ls` | List providers and middlewares |

**Bundle Publisher**

| Command | Description |
| --- | --- |
| `bundle:make` | Create a bundle scaffold |
| `bundle:add` | Add files or folders to bundle.json |
| `bundle:export` | Export a bundle |
| `bundle:ls` | List bundles and their status |
| `bundle:info` | Show information on a bundle |

## Testing

```bash
composer test
composer lint
```

## License

MIT