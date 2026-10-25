# Strukt Framework Documentation

Annotation-driven application kernel, facades, and console for the `strukt4`
package set.

This directory holds the reference documentation for `strukt/framework`. It sits
next to [`changelog/`](changelog/README.md), which records what changed in each
release, and [`BACKLOG.md`](BACKLOG.md), which tracks work not yet done.

## Start here

| Document | What it covers |
| --- | --- |
| [architecture.md](architecture.md) | The big picture: layers, components, and the request lifecycle. |
| [getting-started.md](getting-started.md) | Install, scaffold an application, and run it. |

## Guides

| Document | What it covers |
| --- | --- |
| [routing.md](routing.md) | Modules, routers, route annotations, middleware, and dispatch. |
| [forms-and-validation.md](forms-and-validation.md) | Form classes, validation rules, and custom validators. |
| [dependency-injection.md](dependency-injection.md) | The PSR-11 container, bindings, and autowiring. |
| [extensions.md](extensions.md) | Automatic discovery of validators, middleware, and providers under `lib/App`. |
| [templates.md](templates.md) | Project-owned scaffolding templates and how they are resolved. |
| [bundles.md](bundles.md) | The package manifest, discovery, and the publisher commands. |
| [configuration.md](configuration.md) | `cfg/*.ini` sections, `.env` keys, and the configuration cache. |
| [console.md](console.md) | Every command, command sections, and writing your own. |
| [xhttp.md](xhttp.md) | The development-only in-process HTTP client binary. |
| [annotations.md](annotations.md) | The annotation reference and the parser. |
| [helpers.md](helpers.md) | The global helper functions the framework relies on. |
| [testing.md](testing.md) | Running the suite, linting, and writing tests. |

## Project

| Document | What it covers |
| --- | --- |
| [BACKLOG.md](BACKLOG.md) | Outstanding and planned work for the framework. |

## At a glance

```php
use Strukt\Framework\Application;

$app = Application::make();
$app->register(new AuthModule());
$app->run();
```

- **Application kernel** — one orchestrator that discovers modules, wires
  providers and middleware, registers routes, and dispatches requests.
- **Annotation driven** — routers, forms, middleware, providers, and validators
  declare their configuration in docblocks, no boilerplate registration.
- **Ports and adapters** — the kernel depends on a small `Kernel` port, not the
  concrete router, so the transport is swappable.
- **PSR-11 container** — collaborators are resolved from a container with
  constructor autowiring.
- **Zero-config extensions** — drop a validator, middleware, or provider under
  `lib/App` and it is picked up automatically.

## Package requirements

- PHP `>= 8.2`
- `strukt/base`, `strukt/commons`, `strukt/contract`, `strukt/router`,
  `strukt/cache`, `strukt/event`, `strukt/form`, `strukt/rules`, `strukt/fs`,
  `strukt/generator`, `strukt/console`

See [`composer.json`](../composer.json) for the authoritative list.
