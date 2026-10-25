# Helpers

The framework defines a handful of global helper functions in
[`src/helpers.php`](../src/helpers.php) and relies on the helpers published by the
other `strukt4` packages. This document lists both.

Helpers follow the `helper_add()` convention from `strukt/base`: a package claims
a name once, and the function is only defined when the claim succeeds. That keeps
multiple packages from clashing.

## Framework helpers

### `container(?Container $container = null): Container`

Returns the shared PSR-11 container, optionally installing a different one.

```php
$kernel = container()->get(Strukt\Framework\Contract\Kernel::class);
container(new Container()); // install a fresh shared container
```

On first call it also registers the framework bindings (`Kernel`, `Registrar`,
`Configuration`, `ModuleStore`, `BundlesRepositoryInterface`). See
[dependency-injection.md](dependency-injection.md).

### `repos(?string $type = null): array`

Lists the configured packages.

```php
repos();              // ['core' => 'Strukt\Bundle\Core', ...]
repos('published');   // ['core']
repos('installed');   // ['core']
```

Backed by `BundlesRepository` / `Strukt\Bundle\Bundles`. See [bundles.md](bundles.md).

### `core(string $alias, ?array $args = null): object`

Resolves an application facet by module alias through the `provider.core` event.

```php
core('au.ctr.User', ['request' => request()]);
```

Raises `The [provider.core] provider is not registered.` when the facet provider
is absent. See [routing.md](routing.md).

### `bundle(string $name, string $mode = 'App:Cli'): object`

Returns an adapter over a package manifest.

```php
bundle('core')->get('providers');
bundle('core')->get('middlewares');
bundle('core')->get('requirements');
```

See [bundles.md](bundles.md) for the full list of accepted keys.

### `hashfn(?string $type = null): callable`

Returns a password hashing callable, defaulting to `sha1`.

```php
$hash = hashfn()('p@55w0rd');        // sha1
$hash = hashfn('sha256')('p@55w0rd'); // when installed
```

An unknown algorithm falls back to `sha1`.

## Framework-wide helpers

These come from the other `strukt4` packages but are part of the daily
vocabulary of framework code.

### Environment and configuration

```php
env('root_dir');                    // read an environment value
config('app.name');                 // read a config section or dot path
```

`Strukt\Env` (`strukt/base`) owns environment values; the framework merges
`cfg/*.ini` into the `config()` registry. See
[configuration.md](configuration.md).

### Registry and events

```php
reg('nr.routes.shop');              // read the registry
reg('nr.modules', ['shop' => ...]); // write the registry
event('provider.core');             // fetch an event
```

The `reg()` registry is the discovery backbone (`nr.*` keys, `router`). Events
are how `core()` reaches the facet provider.

### Caching and routing

```php
cache('app');                       // select a cache file
router();                           // the shared router instance
route('/shop', 'GET');              // dispatch a request to a path
request(['name' => 'ada']);         // build a request
response(200);                      // build a response
```

### Forms

```php
form(LoginForm::class, ['username' => 'ada']); // build + submit + validate
form_check(['email' => 'x'], ['email' => 'email']); // validate values
```

### Reflection, strings, errors

```php
ref($object);                       // a Strukt\Ref wrapper
str('Hello World')->toSnake()->yield();
raise('Something is broken');       // throw (code 500 by default)
```

## Helper plumbing

```php
helper('framework');   // load a package's helper file
helper_add('repos');   // claim a helper name (returns false if taken)
```

`helpers.php` calls `helper('framework')` and guards each `function_exists`
definition, so an application can predefine or skip any helper.
