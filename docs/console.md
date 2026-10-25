# Console

The framework ships a console dispatcher and a set of application commands. This
document covers the built-in commands, how commands are grouped, and how to write
your own.

## The binary

Composer publishes `bin/strukt` to `vendor/bin/strukt`. The binary resolves
`vendor/autoload.php` relative to the **current working directory**, sets the
framework environment defaults, registers the application autoloader, and hands
control to [`Shell`](../src/Strukt/Framework/Shell.php):

```php
require getcwd() . '/vendor/autoload.php';

Env::withFile(getcwd() . '/.env', false);
// defaults: root_dir, rel_appsrc, rel_app_lib, rel_mod_ini, serve_*, is_dev
Autoload::register();

exit((new Shell())->run($argv));
```

Run it from your project root:

```bash
vendor/bin/strukt list
vendor/bin/strukt help module:make
vendor/bin/strukt route:ls
```

The framework also publishes a sibling binary, [`xhttp`](xhttp.md), a
development-only in-process HTTP client for exercising routes from the terminal.

## Shell behaviour

| Input | Result |
| --- | --- |
| `strukt list` / `strukt commands` / no command | Renders the command sections. |
| `strukt help` | Renders the command sections. |
| `strukt help <command>` | Renders help for one command. |
| `strukt <command> --help` / `-h` | Renders help for that command. |
| `strukt docs` | Writes one markdown file per command to `docs/commands/`. |
| `strukt <unknown>` | `Error: Unknown command '<name>'. Use 'list' to see available commands.` (exit `1`). |

A command is resolved to an instance by the `CommandFactory` (the default
[`ContainerCommandFactory`](../src/Strukt/Framework/Console/ContainerCommandFactory.php)
autowires constructor dependencies from the container), then
`execute(array $arguments, array $options): int` is called and its exit code is
returned.

## Command sections

`list`/`help` group commands by the sections declared in
[`cfg/cmd.ini`](../cfg/cmd.ini). Each section maps its `cmd[]` list onto the
discovered definitions and carries an optional `title`. Commands that are
discovered but not listed anywhere are appended under a final ungrouped heading
so nothing is hidden. A section that resolves to no commands is skipped, so a
group whose commands are conditionally unavailable never appears.

```ini
[main]
title = Strukt
cmd[] = app:make
cmd[] = app:reload
cmd[] = app:exec
cmd[] = middleware:make
cmd[] = provider:make
cmd[] = validator:make
cmd[] = route:make
cmd[] = route:ls
cmd[] = module:make
cmd[] = shell:exec
cmd[] = sys:ls

[cache]
title = Cache
cmd[] = cache:clear
cmd[] = cache:make
cmd[] = cache:reset

[pub-bundle]
title = Bundle Publisher
cmd[] = bundle:make
cmd[] = bundle:add
cmd[] = bundle:export
cmd[] = bundle:ls
cmd[] = bundle:info

[bundle-db]
title = Database
cmd[] = model:generate
cmd[] = db:create
```

The `[bundle-db]` group is conditional: its commands (`model:generate`,
`db:create`) require Strukt Ar, so the generated `cfg/cmd.ini` only includes the
section when `strukt/ar` (or `strukt/orm`, which requires it) is installed.


## Built-in commands

### Application

| Command | Usage | Description |
| --- | --- | --- |
| `app:make` | `app:make [<app_name>]` | Generate application scaffolding from the project's `templates/`, including `index.php` and the full `cfg/` and `templates/` sets. Prompts for the name when omitted. |
| `app:reload` | `app:reload` | Generate `lib/App/Loader.php`; ensure `.env`, `index.php`, the `cfg/` and `templates/` sets, and the `lib/App/Provider/Facet.php` provider. |
| `app:exec` | `app:exec [<port>] [--log]` | Run on the PHP built-in server. |
| `module:make` | `module:make <app_name> <module_name> <alias_name>` | Generate a module with its facet folders. |
| `route:make` | `route:make [<module>] [<name>] [<route>] [--method=] [--permission=] [--function=]` | Generate a module router or append a route method. |
| `route:ls` | `route:ls [<filter>]` | Boot the app and list registered routes. |
| `shell:exec` | `shell:exec` | Boot the app, then start a PsySH session with a `core` accessor (requires `psy/psysh`). |

`app:make` prompts for the application name when it is omitted. The prompt reads
from the controlling terminal, so it works from Composer's
`post-create-project-cmd` hook even when Composer captures the script output. It
fails with a clear error when no name is given outside an interactive terminal.

Alongside the generated sources, `app:make` writes the project's `cfg/` set:
the application-specific `cfg/app.ini` from `templates/cfg/app.sgf` and a
copy of every framework default (`cache.ini`, `cmd.ini`, `module.ini`,
`repo.ini`). `app:reload` backfills any of those defaults that are missing
without ever overwriting an existing file, so projects scaffolded before a
section existed pick it up on the next reload. See
[configuration.md](configuration.md) for how the project copy overrides the
framework defaults.

`app:make` and `app:reload` likewise scaffold the project's `templates/`
directory, and the generator commands render from the project copy before the
framework default. See [templates.md](templates.md).

### Scaffolding

| Command | Usage | Description |
| --- | --- | --- |
| `middleware:make` | `middleware:make <name>` | Create `lib/App/Middleware/<Name>.php`. |
| `provider:make` | `provider:make <name>` | Create `lib/App/Provider/<Name>.php`. |
| `validator:make` | `validator:make <name>` | Create `lib/App/Validator/<Name>.php`. |

The three scaffolder commands camel-case the name, render the matching
`templates/*.sgf`, and write it under `rel_app_lib`. Templates resolve
project-first — see [templates.md](templates.md). The generated middleware
and provider carry an `@Name(<name>)` annotation, which is how the pipeline and
container address them. The generated validator is discovered automatically —
see [extensions.md](extensions.md).

### Cache

| Command | Usage | Description |
| --- | --- | --- |
| `cache:clear` | `cache:clear [<facet>]` | Remove `.cache` or a single `.cache/<facet>.json`, and reset the memory driver. |
| `cache:make` | `cache:make` | Rebuild the application configuration cache. |
| `cache:reset` | `cache:reset` | `cache:clear` followed by `cache:make`. |

`cache:make` calls `Configuration::create(config('app.type'))` and stores the
result under `cache('app')`. See [configuration.md](configuration.md).

### System

| Command | Usage | Description |
| --- | --- | --- |
| `sys:ls` | `sys:ls [<type>]` | List configured providers and middlewares (`providers` or `middlewares`). |

### Package publisher

See [bundles.md](bundles.md).

| Command | Usage | Description |
| --- | --- | --- |
| `bundle:make` | `bundle:make <name>` | Create `src/Strukt/Package/Pkg<Name>.php` and `bundle.json`. |
| `bundle:add` | `bundle:add <folder>` | Append files/folders to `bundle.json`. |
| `bundle:export` | `bundle:export <name>` | Export the package into `bundle-<name>/`. |
| `bundle:ls` | `bundle:ls` | List packages and their status. |
| `bundle:info` | `bundle:info <name>` | Show a bundle's manifest, metadata, and per-type settings. |

### Database (Strukt Ar)

These commands bridge to [`strukt/ar`](https://github.com/strukt/ar) and are
**only offered when it is installed** (see [Conditional commands](#conditional-commands)).
Their targets default to the application source tree (`rel_appsrc`) rather than
Ar's standalone `build/Models` / `App\Model` defaults.

| Command | Usage | Description |
| --- | --- | --- |
| `model:generate` | `model:generate [--database=<db>] [options]` | Generate Strukt Ar models from the database schema. |
| `db:create` | `db:create [--database=<db>] [options]` | Create tables from the schemas declared by models in `--dir`. |

`--namespace` defaults to the application root namespace (the first-level
directory under `rel_appsrc`, matching the autoloader) and `--dir` defaults to
that directory. Both accept the same database options as Ar (`--driver`,
`--host`, `--port`, `--user`, `--password`, `--env`).

> **Note:** `db:create` scans `--dir` recursively and loads every candidate
> class. Because scaffolded modules ship a `Tests/` directory extending
> `PHPUnit\Framework\TestCase`, its default target can fail when PHPUnit is not
> installed. Point it at a models-only directory with `--dir` (and optionally
> `--namespace`) in that case.

## Conditional commands

A command may implement `Strukt\Framework\Console\ConditionalCommand`. The
repository asks each such command's `isAvailable(): bool` (static) before parsing
it and skips it entirely when it returns `false`, so optional integrations never
advertise commands the installation cannot run. Unavailable commands also reject
dispatch (they are simply unknown).

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Framework\Console\ConditionalCommand;

final class Export implements CommandInterface, ConditionalCommand
{
    public static function isAvailable(): bool
    {
        return class_exists(\Some\Optional\Dependency::class);
    }

    public function execute(array $arguments, array $options): int
    {
        return 0;
    }
}
```

## Writing a command

A command is any class implementing
`Strukt\Console\Runtime\CommandInterface`. Its configuration lives in the class
docblock, parsed by `Strukt\Console\Parser\DocBlockParser`.

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Strukt\Console\Runtime\CommandInterface;

/**
 * @command greeting:say
 * @description Print a greeting
 * @usage greeting:say <name> [--shout]
 * @argument name
 * @description Who to greet
 * @required
 * @option shout
 * @short s
 * @flag
 * @description Upper case the greeting
 */
final class SayHello implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $name = (string) ($arguments['name'] ?? 'world');
        $message = sprintf('Hello %s!', $name);

        echo ($options['shout'] ?? false) ? strtoupper($message) : $message;
        echo "\n";

        return 0;
    }
}
```

### Docblock annotations

| Annotation | Applies to | Meaning |
| --- | --- | --- |
| `@command <name>` | class | The command name. **Required.** |
| `@description <text>` | class, argument, option | Help text. |
| `@usage <line>` | class | Usage string shown in help. |
| `@argument <name>` | class | Declares a positional argument. |
| `@option <name>` | class | Declares an option (long `--name`). |
| `@required` | argument, option | The value must be supplied. |
| `@flag` | option | Boolean switch; implies `@type bool`. |
| `@short <char>` | option | Single-character alias, e.g. `-s`. |
| `@type <type>` | argument, option | Value type (`string`, `int`, `bool`, …). |
| `@default <value>` | argument, option | Default value. |
| `@enum a,b,c` | argument, option | Restricts values to a set. |

`@description` attaches to the most recently declared argument or option until
the next declaration, so the order of lines matters.

### Arguments and options

`execute()` receives:

- `$arguments` — positional arguments keyed by name (`$arguments['name']`).
- `$options` — options keyed by long name; flags are booleans, others are strings
  or parsed scalars. Short flags are resolved to their long name.

The `Input` parser understands `--option value`, `--option=value`, `-s`, and
positional arguments in declaration order.

### Discovery

`CommandRepository` collects command classes from three sources:

1. the framework directory `src/Strukt/Framework/Console/Command` (when
   `base_commands` is on),
2. any additional directories passed to `Shell`,
3. the commands contributed by published packages (`Configuration::get('commands')`).

The repository parses each class, skipping any class whose docblock is missing or
malformed, and keys the definitions by command name. Classes implementing
`ConditionalCommand` are dropped first when their dependency is absent.

## Programmatic use

```php
use Strukt\Framework\Shell;

exit((new Shell())->run($argv));
```

`Shell` accepts four constructor arguments:

```php
new Shell(
    base_commands: true,          // load the framework commands
    directories: ['app/Command'], // extra command directories
    docsDirectory: null,          // where `docs` writes markdown
    factory: null,                // defaults to ContainerCommandFactory
);
```

Use [`PlainCommandFactory`](../src/Strukt/Framework/Console/PlainCommandFactory.php)
when you do not want container instantiation.
