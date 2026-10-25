# Configuration

Configuration comes from two places:

1. **`cfg/*.ini`** sections, loaded into the shared `config()` registry.
2. **`.env` / `Env`** values, used for runtime paths and the built-in server.

Package settings (providers, middleware, commands) are aggregated separately and
cached — see [bundles.md](bundles.md) and the [configuration cache](#configuration-cache).

## Config registry

Configuration is layered, most specific last:

1. **Package defaults.** When `helpers.php` loads it merges every framework
   `cfg/*.ini` file into the shared config registry. Each file is loaded **only
   when its section is not already set**, so an application can override any
   section before the framework loads:

   ```php
   foreach (glob(dirname(__DIR__) . '/cfg/*.ini') as $file) {
       $section = pathinfo($file, PATHINFO_FILENAME);
       if (config($section) !== null) {
           continue;
       }

       config($section, parse_ini_file($file, true, INI_SCANNER_TYPED));
   }
   ```

2. **Project overrides.** [`ConfigLoader::load()`](../src/Strukt/Framework/ConfigLoader.php)
   merges the project's own `<root>/cfg/*.ini` over those defaults. It runs from
   `Shell::run()` and `Application::make()`, so both the console and the HTTP
   entry point see the same values. Project keys win, but keys the project does
   not override are preserved:

   ```php
   ConfigLoader::load(ConfigLoader::root());
   ```

The project copies are written for you: `app:make` scaffolds the full set and
`app:reload` backfills any missing file without overwriting an existing one (see
[console.md](console.md)). Each is rendered from its own template under
`templates/cfg/*.sgf`, so a project's files are independent of the
framework's `cfg/*.ini` defaults — see [templates.md](templates.md).

Read values with dot paths:

```php
config('app.name');
config('module.folders.rtr');   // "Router"
```

## Shipped sections

| File | Section | Purpose |
| --- | --- | --- |
| [`cfg/app.ini`](../cfg/app.ini) | `app` | Application name and the global middleware/provider lists. |
| [`cfg/module.ini`](../cfg/module.ini) | `module` | Module facet folders. |
| [`cfg/cache.ini`](../cfg/cache.ini) | `cache` | Cache flags. |
| [`cfg/repo.ini`](../cfg/repo.ini) | `repo` | Configured packages. |
| [`cfg/cmd.ini`](../cfg/cmd.ini) | `cmd` | Console command sections. |

Every one of these is generated into the project by `app:make` and `app:reload`
from a matching `templates/cfg/<section>.sgf`, so the framework file under
`cfg/` is only the fallback used before the project copy exists.

### `app.ini`

```ini
app-name = Payroll
middlewares[] = sess
middlewares[] = auth
middlewares[] = authz
middlewares[] = valid
providers[] = valid
providers[] = facet
```

`app:make` writes an application-specific `cfg/app.ini` (from
`templates/cfg/app.sgf`) with the app name; `app:reload` backfills it only
when `templates/cfg/app.sgf` resolves an application name. `config('app.name')` and
`config('app.type')` are read by class resolution and configuration discovery.

### `module.ini`

```ini
[folders]
ctr = Controller
rtr = Router
frm = Form
tes = Tests
```

Maps the short facet keys used in registry keys (`nr.`<alias>`.<facet>.<name>`) to
directory names on disk. The [`Registrar`](../src/Strukt/Framework/Module/Registrar.php)
inverts this map to scan a module.

### `repo.ini`

```ini
core = Core
```

Lists the packages available to an application. A value of `Extender` resolves to
`App\<Name>`; anything else resolves to `Strukt\Bundle\<Name>`. See
[bundles.md](bundles.md).

### `cmd.ini`

Declares the console command groups. See [console.md](console.md). The
`[bundle-db]` group is generated only when Strukt Ar is installed, since its
`model:generate`/`db:create` commands depend on it.

### `cache.ini`

```ini
disable = false
```

## Environment values

`.env` values are read with the `env()` helper, and the framework sets defaults
for the keys below when they are missing. `bin/strukt` sets the path and server
keys before booting; `Application::make()` can already rely on them.

| Key | Default | Meaning |
| --- | --- | --- |
| `root_dir` | project root (cwd) | Absolute project root. |
| `rel_appsrc` | `app/src` | Application source directory (module namespaces). |
| `rel_app_lib` | `lib/App` | Application library (`App\` namespace, extendables). |
| `rel_mod_ini` | `cfg/module.ini` | Module folder configuration. |
| `rel_loader_sgf` | built-in `templates/lib/App/Loader.sgf` | Optional custom loader template. |
| `serve_host` | `127.0.0.1` | Built-in server host. |
| `serve_port` | `8080` | Built-in server port. |
| `serve_ctx` | `.` | Document root/context for the built-in server. |
| `is_dev` | `true` | Development flag. |

`Autoload::register()` reads `root_dir`, `rel_app_lib`, and `rel_appsrc`.
[`Environment::ensure()`](../src/Strukt/Framework/Environment.php) writes the
default `.env` when the file is missing (never overwriting it):

```php
Environment::ensure($root); // creates <root>/.env on first run
```

`app:reload` and `app:exec` call it automatically.

## Module configuration

Besides the facet folders, the module namespace is discovered from `rel_appsrc`.
`Reloader` scans `app/src/*/*Module` and renders `lib/App/Loader.php` with one
`register()` per module:

```php
$this->app->register(new \Payroll\AuthModule\PayrollAuthModule());
```

Override the loader template with `rel_loader_sgf`.

## Configuration cache

[`Configuration`](../src/Strukt/Framework/Configuration.php) aggregates the
providers, middleware, and commands contributed by published packages for the
active application type, and caches the result per type:

```php
$configuration = new Configuration();           // resolves cache/app.type/repos
$configuration->get('providers');               // list<class-string>
$configuration->get('middlewares');
$configuration->get('commands');
$configuration->getAliases();                   // resolved facet aliases
$configuration->getInjectables();                // App\Injectable closures (or null)
```

The cache is keyed by `config('app.type')` and stored with the `app` cache
driver. Manage it with:

```bash
vendor/bin/strukt cache:make     # Configuration::create() + save
vendor/bin/strukt cache:clear    # remove .cache (or a facet file)
vendor/bin/strukt cache:reset    # clear + make
```

`Configuration::create($appType, $packages)` is the pure discovery function;
`cache:make` calls it directly.

## Environment object

`Strukt\Env` (from `strukt/base`) backs `Env::has()`, `Env::get()`,
`Env::set()`, `Env::withFile()`, and `Env::reset()`. `env('key')` is the helper
form. Tests use `Env::set()`/`Env::reset()` to point the framework at throwaway
roots.
