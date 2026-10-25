# Dependency Injection

The framework ships a small PSR-11 container with constructor autowiring.
Collaborators are resolved from a shared instance exposed by the `container()`
and `app()` helpers, and every binding falls back to the global accessors so an
application works without a container.

## The container

`Strukt\Container\Container` implements
`Psr\Container\ContainerInterface` and supports four kinds of binding:

| Method | Behaviour |
| --- | --- |
| `set($id, callable $factory)` | Resolves once and reuses the result (shared). |
| `factory($id, callable $factory)` | Resolves a fresh value on every `get()`. |
| `instance($id, mixed $value)` | Returns a prebuilt value. |
| `alias($alias, $id)` | Points one identifier at another. |

```php
use Strukt\Container\Container;

$container = new Container();

$container->set(Cache::class, fn (Container $c) => cache('app'));
$container->factory(RequestId::class, fn () => new RequestId(bin2hex(random_bytes(8))));
$container->instance(Clock::class, new SystemClock());
$container->alias('cache', Cache::class);

$container->get(Cache::class);      // shared
$container->has('cache');           // true (alias resolved)
```

Every factory receives the container, so a binding can depend on others.

## Autowiring

`get()` falls back to `make()`, which reflects the class constructor and
resolves each parameter:

1. a named argument passed to `make($class, $parameters)` wins,
2. a non-builtin typed parameter is resolved recursively from the container,
3. otherwise a declared default value is used,
4. otherwise resolution fails.

```php
final class Mailer
{
    public function __construct(private Transport $transport) {}
}

$mailer = $container->make(Mailer::class);          // autowired
$mailer = $container->make(Mailer::class, [
    'transport' => new SmtpTransport('localhost'),
]);                                                  // explicit
```

Failure modes:

- unknown identifier → `NotFoundException`
  (`No entry was found for identifier [...]`),
- unresolvable constructor parameter → `ContainerException`
  (`Cannot resolve parameter [$x] of [Y]`),
- non-instantiable class → `ContainerException`.

## The shared container

`container()` returns a process-wide instance:

```php
$kernel = container()->get(Kernel::class);
$controller = container()->make(\App\Auth\Login::class);
```

`container($another)` installs a different container as the shared instance
(useful in tests).

The container class itself owns that shared instance, so the low-level `app()`
helper (from `strukt/base`) resolves against the very same container:

```php
app();                                    // the shared container
app(Kernel::class);                       // container()->get(...)
app(\App\Auth\Login::class, ['guard' => 'web']); // built with overrides
```

## Framework bindings

When `helpers.php` loads, it binds the framework collaborators on the shared
container:

| Identifier | Resolves to |
| --- | --- |
| `Kernel` | [`RouterAdapter`](../src/Strukt/Framework/Router/RouterAdapter.php) over the shared `router()` |
| `Registrar` | Module indexer, backed by the `ModuleStore` |
| `Configuration` | Cached package settings |
| `ModuleStore` | [`RegistryStore`](../src/Strukt/Framework/Module/Storage/RegistryStore.php) (the shared `reg()` registry) |
| `BundlesRepositoryInterface` | [`BundlesRepository`](../src/Strukt/Framework/Bundle/BundlesRepository.php) (the static `Strukt\Bundle\Bundles`) |

```php
$container = container();
$container->instance(ModuleStore::class, new MemoryStore());   // replace one
$container->factory(Registrar::class, fn (Container $c) => new Registrar(
    $c->get(ModuleStore::class),
    ['ctr' => 'Controller', 'rtr' => 'Router'],
));
$container->set(Kernel::class, fn () => new RouterAdapter(new Router()));
```

`Kernel` is registered as a shared binding, so `container()->get(Kernel::class)`
returns the same adapter the application uses. `Registrar` is a factory, so each
resolution is isolated — which is what lets tests index modules into a private
store.

## Console commands

Commands are instantiated through
[`ContainerCommandFactory`](../src/Strukt/Framework/Console/ContainerCommandFactory.php),
so a command may declare constructor dependencies and have them autowired:

```php
final class GreetCommand implements CommandInterface
{
    public function __construct(private Greeter $greeter) {}

    public function execute(array $arguments, array $options): int
    {
        echo $this->greeter->greet((string) $arguments['name']);

        return 0;
    }
}
```

Use `PlainCommandFactory` if you prefer direct instantiation.

## Providers

A provider is a class implementing `Strukt\Contract\ProviderInterface`
(`register()`, `boot()`, `provides()`). Providers are run by
[`Bootstrap`](../src/Strukt/Framework/Bootstrap.php) — resolved through the
container when one is available, otherwise built directly. They are an
application-level extension point for registering bindings:

```php
namespace App\Provider;

use Strukt\Contract\ProviderInterface;

/**
 * @Name(mail)
 */
final class Mail implements ProviderInterface
{
    public function register(): void
    {
        container()->set(\App\Mailer::class, fn () => new \App\Mailer());
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [\App\Mailer::class];
    }
}
```

Discover them automatically by dropping the file under `lib/App/Provider` — see
[extensions.md](extensions.md). Providers configured through a package are listed
in [bundles.md](bundles.md).

## Adding constructor dependencies to framework classes

The framework classes accept their collaborators through optional constructor
arguments and fall back to the shared globals when omitted:

```php
new Configuration($cache, 'App:Idx', $packages);
new Registrar($store, ['ctr' => 'Controller']);
new Bootstrap();   // static run()
```

This keeps the classes usable both standalone (with explicit dependencies) and
through the container (with the defaults).
