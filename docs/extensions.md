# Extensions (automatic `lib/App` discovery)

Extendables are framework extension points that live under the application
library (`rel_app_lib`, default `lib/App`) and are discovered **without
configuration**. Drop a class implementing a known interface into the library
and the framework picks it up on boot.

## Supported extendables

| Interface | Kind | Effect |
| --- | --- | --- |
| `Strukt\Framework\Contract\Validator` | validator | Registered on the shared rule registry; addressable from a form annotation. |
| `Strukt\Contract\MiddlewareInterface` | middleware | Added to the kernel middleware pipeline (and named by `@Name`). |
| `Strukt\Contract\ProviderInterface` | provider | `register()`ed and `boot()`ed during bootstrap. |

[`Extendables`](../src/Strukt/Framework/Extendables.php) performs one recursive
scan of the library directory, derives each class as `App\<relative path>`, skips
abstract classes/interfaces/traits, and classifies by the first matching
interface (validator first, then middleware, then provider).

## Discovery lifecycle

```php
$extendables = Extendables::discover(); // scans lib/App (cached per root+lib)
$extendables->validators();             // ['phone' => 'App\Validator\Phone', ...]
$extendables->middlewares();            // ['App\Middleware\Audit', ...]
$extendables->providers();              // ['App\Provider\Mail', ...]

$extendables->each(function (string $class, string $kind, ?string $name): void {
    // invoked for every discovered class
});

$extendables->register();               // registers validators on the rule registry
```

Discovery is memoised per `(root, lib)` pair. `register()` accepts an optional
`RuleRegistry` (defaults to the shared one) and returns `$this` for chaining.
`flush()` clears the cache (used in tests).

Where it runs:

- `Application::make()` calls `Extendables::discover()->register()` right after
  `Autoload::register()`, so custom rules are available before anything
  validates.
- [`Bootstrap::run()`](../src/Strukt/Framework/Bootstrap.php) discovers again and
  merges the middleware and providers with those configured for the application
  (deduplicated) before wiring them onto the kernel.

```php
$providers = array_unique(array_merge(
    $configuration?->get('providers') ?? [],
    $extendables->providers(),
));
```

## Validators

The rule name is the class short name in snake case, or the value of an `@Name`
annotation. See [forms-and-validation.md](forms-and-validation.md) for the full
contract.

```php
namespace App\Validator;

use Strukt\Framework\Contract\Validator;
use Strukt\Rules\Contract\FieldSource;

/**
 * @Name(phone)
 */
final class Phone implements Validator
{
    public function __construct(private string $field, private array $params = []) {}

    public function passes(mixed $value, FieldSource $fields): bool { /* ... */ }
    public function message(): string { /* ... */ }
    public function bails(): bool { return false; }
}
```

Discovery registers it as a factory, so each field gets its own instance
configured with the field name and parsed parameters:

```php
$registry->extend('phone', fn (string $field, array $params) => new Phone($field, $params));
```

A form can then use `@Phone()`.

## Middleware

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
        return $next($request, $response);
    }
}
```

Bootstrap adds it to the kernel globally and, because of `@Name(audit)`, also
registers it as the named middleware `audit` (so routes can reference it with
`@Middleware(audit)`).

## Providers

```php
namespace App\Provider;

use Strukt\Contract\ProviderInterface;

/**
 * @Name(mail)
 */
final class Mail implements ProviderInterface
{
    public function register(): void { /* container()->set(...) */ }
    public function boot(): void {}
    public function provides(): array { return [\App\Mailer::class]; }
}
```

`register()` runs before `boot()` for every provider. See
[dependency-injection.md](dependency-injection.md).

## Facet annotation

Middleware and providers may also declare facet metadata in their class
docblock, read by
[`Injectable\Facet`](../src/Strukt/Framework/Injectable/Facet.php):

| Annotation | Meaning |
| --- | --- |
| `@Name(x)` | The alias; required for the facet to be recognised. |
| `@Required()` | Marks the facet as required. |
| `@Requires(x)` | Declares a registry dependency. |
| `@Inject(x)` | Declares the injectable key. |

A facet without `@Name` is ignored. `@Name` is what makes an extendable
addressable (named middleware, validator rule name).

## Scaffolding

```bash
vendor/bin/strukt validator:make phone     # lib/App/Validator/Phone.php
vendor/bin/strukt middleware:make audit    # lib/App/Middleware/Audit.php
vendor/bin/strukt provider:make mail       # lib/App/Provider/Mail.php
```

Each command camel-cases the name, renders the matching `templates/*.sgf`, and
writes the file under `rel_app_lib`. The generated middleware and provider carry
`@Name(<name>)`; the generated validator carries `@Name(<name>)` too. Templates
are resolved project-first — see [templates.md](templates.md).

Because the library is autoloaded at runtime (`App\` → `lib/App`), newly created
files are usable immediately without a Composer dump.
