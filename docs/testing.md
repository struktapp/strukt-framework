# Testing

The framework is tested with [Pest](https://pestphp.com) on top of PHPUnit. This
document covers running the suite, the test layout, and the patterns the tests
use to keep discovery deterministic.

## Running the suite

```bash
composer test    # pest
composer lint    # php -l on every source/test file
```

Or directly:

```bash
./vendor/bin/pest
./vendor/bin/pest tests/Unit/ApplicationTest.php
./vendor/bin/pest --filter=validators
```

`phpunit.xml` defines a single `Unit` suite over `tests/Unit`, with
`vendor/autoload.php` as the bootstrap. `tests/Pest.php` binds every test in
`tests/Unit` to `Tests\TestCase`:

```php
uses(Tests\TestCase::class)->in('Unit');
```

The lint script is:

```bash
find src tests bin -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

Filter out the success lines (`No syntax errors detected in ...`) when you only
care about failures.

## Layout

```
tests/
├── Pest.php                 # Pest bootstrap (uses Tests\TestCase)
├── TestCase.php             # base case
├── Fixtures/                # shared fixture classes
│   └── User.php
├── ShopModule/              # a real module used by routing tests
│   ├── ShopModule.php
│   └── Router/Index.php
└── Unit/                    # the suite
    ├── ApplicationTest.php
    ├── AppExecTest.php
    ├── AppMakeTest.php
    ├── AutoloadTest.php
    ├── ConfigTest.php
    ├── ConfigurationTest.php
    ├── ContainerTest.php
    ├── EntityTest.php
    ├── EnvironmentTest.php
    ├── ExtendablesTest.php
    ├── FormTest.php
    ├── PackageCommandTest.php
    ├── RegistrarTest.php
    ├── ReloaderTest.php
    ├── RouteListTest.php
    ├── ShellTest.php
    ├── ValidatorMakeTest.php
    └── ValidatorMappingTest.php
```

## Patterns

### Isolated routers

Inject a fresh `Strukt\Router\Router` so a test never touches the shared
instance:

```php
beforeEach(function () {
    $this->router = new Router();
    $this->application = Application::make($this->router);
});

it('turns annotated router methods into routes', function () {
    $this->application->register(new ShopModule());
    $this->application->init();

    expect($this->router->collection()->all())->not->toBeEmpty();
});
```

`Application::make($router)` wraps the supplied router; `init()` registers the
routes against it.

### Isolated stores

`Registrar` accepts a `MemoryStore`, so module indexing can be asserted without
the global registry:

```php
$store = new MemoryStore();
$registrar = new Registrar($store, ['rtr' => 'Router']);
$registrar->register(new ShopModule());

expect($store->get('nr.routes.shop'))->toContain(ShopIndex::class);
```

### Temporary project roots

Commands read the project root from the environment. Point it at a throwaway
directory, then reset:

```php
beforeEach(function () {
    $root = sys_get_temp_dir() . '/strukt-val-' . bin2hex(random_bytes(4));
    Env::set('root_dir', $root);
    Env::set('rel_app_lib', $root . '/lib/App');
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});
```

Use an **absolute** `rel_app_lib` when a command writes relative to it, or the
file lands relative to the current working directory.

### Flushing discovery caches

`Autoload` and `Extendables` memoise per root. Reset them between tests that
change the root:

```php
beforeEach(function () {
    Extendables::flush();
    Autoload::reset();
});

afterEach(function () {
    RuleRegistry::shared()->forget('phone_' . $this->id);
    Extendables::flush();
    Autoload::reset();
    Env::reset();
});
```

Generate unique class names (`bin2hex(random_bytes(4))`) when a test defines
classes in the application library, since PHP cannot redeclare a loaded class.

### Validating pure functions

`Configuration::create()`, `Container`, and the validators take their
collaborators as arguments, so they can be exercised without global state:

```php
it('discovers settings through an injected package repository', function () {
    $settings = Configuration::create('App:Idx', new ConfigurationPackageStub());

    expect($settings['middlewares'])->toContain(Strukt\Router\Middleware\Session::class);
});
```

## What the suite covers

| Area | Tests |
| --- | --- |
| Application, modules, routes | `ApplicationTest`, `RegistrarTest`, `RouteListTest` |
| Forms and validators | `FormTest`, `ValidatorMappingTest`, `ValidatorMakeTest`, `ExtendablesTest` |
| Container | `ContainerTest` |
| Configuration and environment | `ConfigTest`, `ConfigurationTest`, `EnvironmentTest` |
| Console and scaffolding | `ShellTest`, `AppMakeTest`, `AppExecTest`, `PackageCommandTest`, `RouteMakeTest` |
| Runtime | `AutoloadTest`, `ReloaderTest`, `EntityTest` |
