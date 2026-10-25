# Getting Started

This guide scaffolds a new `strukt/framework` application, runs it on the PHP
built-in server, and explains the layout that `app:make` produces.

## Requirements

- PHP `>= 8.2` with `ext-json`, `ext-mbstring`, and the usual HTTP stack.
- A project built on the `strukt4` package set: `base`, `commons`, `contract`,
  `router`, `cache`, `event`, `form`, `rules`, `fs`, `generator`, `console`.

## Install

```bash
composer require strukt/framework
```

The package ships a console binary, so `vendor/bin/strukt` is available once
Composer links bins.

## Create an application

Run the console from your project root (the binary locates `vendor/autoload.php`
relative to the current working directory):

```bash
vendor/bin/strukt app:make payroll
```

`app:make` renders `templates/**` into the project and scaffolds the
project's own `cfg/` and `templates/` sets (see
[configuration.md](configuration.md) and [templates.md](templates.md)).
`Payroll` becomes the application name and namespace; an underscored, spaced, or
hyphenated name such as `pay_roll` becomes `PayRoll`. Omit the name to be
prompted for it — this is how the `strukt/strukt` skeleton installs. The result:

```
.
├── app/
│   └── src/
│       └── Payroll/
│           ├── User.php
│           └── AuthModule/
│               ├── PayrollAuthModule.php     # the module (alias "au")
│               ├── Controller/User.php
│               ├── Form/User.php
│               ├── Router/Index.php
│               └── Tests/UserTest.php
├── cfg/                                      # generated application config
│   ├── app.ini
│   ├── cache.ini
│   ├── cmd.ini
│   ├── module.ini
│   └── repo.ini
├── templates/                                # editable scaffolding templates
├── composer.json
└── vendor/
```

If the application already exists the command raises
`Application [<name>] already exists!`.

## Generate the loader and front controller

```bash
vendor/bin/strukt app:reload
```

`app:reload`:

- ensures `<root>/.env` exists (writing the default `serve_*`, `is_dev`, and
  `rel_*` keys when it does not),
- scaffolds `index.php` when the project has no front controller,
- renders `lib/App/Loader.php` from the module tree.

The loader is auto-generated — **do not edit it**, `app:reload` overwrites it.
It registers one module per `*Module` directory it finds under `app/src`:

```php
final class Loader
{
    private Application $app;

    public function __construct(?Router $router = null)
    {
        $this->app = Application::make($router);
        $this->app->register(new \Payroll\AuthModule\PayrollAuthModule());
    }

    public function getApp(): Application
    {
        return $this->app;
    }
}
```

## Run it

```bash
vendor/bin/strukt app:exec
# Serving on http://127.0.0.1:8080
```

`app:exec` boots the PHP built-in server with the project root as the document
root and `index.php` as the router script, so every URL dispatches through the
application. Environment overrides:

```bash
vendor/bin/strukt app:exec 9000        # port override
vendor/bin/strukt app:exec --log       # enable server logs
```

The generated `index.php` is the runtime front controller:

```php
require __DIR__ . '/vendor/autoload.php';

Autoload::register(__DIR__);

$app = class_exists(Loader::class)
    ? (new Loader())->getApp()
    : Application::make();

$app->run();
```

Visit `http://127.0.0.1:8080/` and `http://127.0.0.1:8080/hello/ada` — the
`AuthModule` router ships with a `welcome` and a `hello` route.

## Autoloading

Application namespaces resolve at runtime through
[`Autoload`](../src/Strukt/Framework/Autoload.php):

| Prefix | Directory |
| --- | --- |
| `App\` | `lib/App` (`rel_app_lib`) |
| `<Name>\` | `app/src/<Name>` (`rel_appsrc`) |

So `Payroll\AuthModule\...` resolves from `app/src/Payroll/AuthModule/...` and
`App\Validator\Phone` resolves from `lib/App/Validator/Phone.php`. Because the
loader is registered at runtime, **no Composer autoload edits or
`composer dump-autoload` are needed** when you add classes.

## Add a module

```bash
vendor/bin/strukt module:make payroll billing bl
```

This creates `app/src/Payroll/BillingModule/PayrollBillingModule.php` with alias
`Bl` (the module name is suffixed with `Module` when missing, and names are
Pascal-cased) and the configured facet folders (`Controller`, `Router`, `Form`,
`Tests`). Re-run `app:reload` to pick it up.

## Add a router

Create a class under `app/src/Payroll/BillingplanModule/Router/` that extends
`Strukt\Framework\Contract\Router`:

```php
namespace Payroll\BillingModule\Router;

use Strukt\Framework\Contract\Router;

final class Index extends Router
{
    /**
     * @Route(/billing)
     * @Method(GET)
     */
    public function index(): string
    {
        return 'Billing works!';
    }
}
```

The `Application` discovers it through the module and registers the route. See
[routing.md](routing.md).

## Minimal programmatic bootstrap

You can also boot the application by hand, without the console:

```php
use Strukt\Framework\Application;

require 'vendor/autoload.php';

$app = Application::make();
$app->register(new Payroll\AuthModule\PayrollAuthModule());
$app->run();
```

## Next steps

- [routing.md](routing.md) — modules, routers, annotations.
- [forms-and-validation.md](forms-and-validation.md) — forms and rules.
- [console.md](console.md) — every console command.
- [configuration.md](configuration.md) — `.env` and `cfg/*.ini`.
- [extensions.md](extensions.md) — automatic `lib/App` discovery.
