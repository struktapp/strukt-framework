# Bundles

A **package** contributes providers, middleware, and console commands to an
application. Packages are the distribution unit for reusable additions that are
not application facets: everything an application needs to install a feature and
have it wired on boot.

## The package contract

A package implements `Strukt\Framework\Contract\Bundle`:

| Method | Purpose |
| --- | --- |
| `getName(): string` | Package identifier. |
| `getCmdName(): string` | Display name used by the console. |
| `getModules(): ?array` | Modules the package installs, or `null`. |
| `getFiles(): ?array` | Files the package installs, or `null`. |
| `isPublished(): bool` | Whether the package is active. |
| `getRequirements(): ?array` | Required package names, or `null`. |
| `preInstall(): void` | Runs before installation. |
| `postInstall(): void` | Runs after installation. |
| `getSettings(string $type): array` | Settings for an application type. |

Only **published** packages contribute to an application's configuration.
`getSettings()` is queried once per application type (see
[configuration.md](configuration.md)):

```php
$settings = $package->getSettings('App:Idx');
// ['providers' => [...], 'middlewares' => [...], 'commands' => [...]]
```

`App:Idx` carries `providers` and `middlewares`; `App:Cli` also carries
`commands`.

## Discovery

[`Configuration`](../src/Strukt/Framework/Configuration.php) iterates every
available package, filters to the published ones, and collects their settings
for the active type. Discovery goes through the
[`BundlesRepositoryInterface`](../src/Strukt/Framework/Contract/BundlesRepositoryInterface.php)
port so it can be stubbed in tests.

The default repository is
[`BundlesRepository`](../src/Strukt/Framework/Bundle/BundlesRepository.php), backed
by [`Strukt\Bundle\Bundles`](../src/Strukt/Bundle/Bundles.php), which resolves the
`repo` config section:

```ini
; cfg/repo.ini
core = Core
```

| Configured value | Resolved class |
| --- | --- |
| `Core` | `Strukt\Bundle\Core` |
| `Extender` | `App\Extender` |
| anything else | `Strukt\Bundle\<Value>` |

`Bundles::available()` lists every configured package and its class;
`Bundles::list('installed')` lists those whose class exists, and
`Bundles::list('published')` those that additionally report
`isPublished() === true`.

The `repos()` helper exposes the same data:

```php
repos();                        // ['core' => 'Strukt\Bundle\Core', ...]
repos('published');             // ['core']
repos('installed');             // ['core']
```

## The core package

`Strukt\Bundle\Core` is the built-in framework package. It is always published
and contributes:

- **Middleware** (filtered to installed classes):
  `sess`, `authz`, `auth`, `valid`, `jwt`, `cors`.
- **Providers**: the `facet` provider (`App\Provider\Facet`).
- **Commands** (under `App:Cli`): the framework console commands.

Its `keep()` helper drops any class that is not installed, so an application
without the optional packages still boots.

## Reading package metadata

The `bundle()` helper returns an adapter over a package manifest:

```php
package('core')->get('providers');
package('core')->get('middlewares');
package('core')->get('commands');
package('core')->get('name');
package('core')->get('requirements');
package('core')->get('is:published');
package('core')->get('settings');       // the whole settings array
```

It accepts `App:Cli` (default) or `App:Idx` as the second argument.

## Writing a package

Given a class `PkgPayroll` in `src/Strukt/Package`:

```php
namespace Strukt\Bundle;

use Strukt\Framework\Contract\Bundle as PackageInterface;

final class PkgPayroll implements PackageInterface
{
    private array $manifest = [
        'cmd_name' => 'Payroll',
        'package'  => 'bundle-payroll',
        'files'    => [],
    ];

    public function getSettings(string $type): array
    {
        $settings = [
            'App:Cli' => ['providers' => [], 'middlewares' => [], 'commands' => []],
            'App:Idx' => ['providers' => [], 'middlewares' => []],
        ];

        return $settings[$type];
    }

    public function getName(): string { return $this->manifest['package']; }
    public function getCmdName(): string { return $this->manifest['cmd_name']; }
    public function getFiles(): ?array { return $this->manifest['files']; }
    public function getModules(): ?array { return null; }
    public function isPublished(): bool { return class_exists(\SomeClass::class); }
    public function getRequirements(): ?array { return null; }
    public function postInstall(): void {}
    public function preInstall(): void {}
}
```

Register it in `cfg/repo.ini`:

```ini
payroll = Payroll
```

Once `isPublished()` returns true, its providers and middleware are wired on
boot and its commands join the console.

> `isPublished()` is the switch that keeps a package dormant until its runtime
> dependency (the class checked) is present.

## Package publisher commands

The publisher commands scaffold and export packages.

### `bundle:make <name>`

Creates the package scaffold:

- `src/Strukt/Package/Pkg<Name>.php` from `templates/package/Pkg_.sgf`,
- `bundle.json` with an empty `files` list.

Run it once; re-running reports `Already exists!`.

### `bundle:add <folder>`

Appends the files under `folder` (or a single file) to `bundle.json` as
project-root-relative paths, deduplicated:

```json
{
    "files": [
        "app/src/Payroll/AuthModule/Router/Index.php"
    ]
}
```

### `bundle:export <name>`

Exports the package into `bundle-<snake-name>/`:

- copies `package/` when present,
- copies `src/Strukt/Package/Pkg<Name>.php`,
- copies `.env` and `.gitignore` when present,
- writes a `composer.json` (`type: strukt-module`, PSR-4 `Strukt\Bundle\` →
  `src/Strukt/Package/`) and a stub `README.md`.

```
bundle-payroll/
├── composer.json
├── README.md
├── package/
└── src/Strukt/Package/PkgPayroll.php
```

### `bundle:ls`

Lists every package except `core` with its status (`published`, `installed`, or
`unavailable`).

### `bundle:info <name>`

Prints a package's name, published flag, requirements, modules, and per-type
settings (commands, middleware, providers). `core` is allowed even though it is
not user-installed.

## Injectables

Beyond providers, a package can publish closures into the container through an
application-level `App\Injectable` class. Method docblocks pair `@Inject(alias)`
with `@Bundle(<package-name>)`:

```php
final class Injectable
{
    /**
     * @Inject(validator)
     * @Bundle(core)
     */
    public function validator(): object
    {
        return new Validator();
    }
}
```

[`Configuration::getInjectables()`](../src/Strukt/Framework/Configuration.php)
reads them, and only the injectables belonging to a **published** package are
returned:

```php
$configuration->getInjectables()?->getConfigs();
// ['validator' => Closure, ...]
```

`Application::make()` resolves the configuration from the container only when
`App\Injectable` exists, so this is opt-in.

## Related

- [extensions.md](extensions.md) — automatic `lib/App` discovery (a lighter
  weight alternative for application-specific validators, middleware, and
  providers).
- [configuration.md](configuration.md) — the configuration cache and `repo`
  section.
- [console.md](console.md) — the publisher commands in the console reference.
