# Templates

The generator commands render `.sgf` templates. Projects own a copy of them
under `<root>/templates/`, so scaffolding output can be customised per project
without editing vendor code.

## Layout mirrors the install path

A template's path under `templates/` is the path its output is written to, with
the `.sgf` extension replaced by the real one (`.php`, `.ini`, …). Placeholders
such as `{{app}}` and `{{class_name}}` appear in both the template path and its
content:

| Template | Output |
| --- | --- |
| `templates/lib/App/Middleware/{{class_name}}.sgf` | `lib/App/Middleware/<Name>.php` |
| `templates/lib/App/Provider/{{class_name}}.sgf` | `lib/App/Provider/<Name>.php` |
| `templates/lib/App/Validator/{{class_name}}.sgf` | `lib/App/Validator/<Name>.php` |
| `templates/lib/App/Loader.sgf` | `lib/App/Loader.php` |
| `templates/lib/App/Provider/Facet.sgf` | `lib/App/Provider/Facet.php` |
| `templates/src/Strukt/Bundle/Bundle{{class_name}}.sgf` | `src/Strukt/Bundle/Bundle<Name>.php` |
| `templates/cfg/<section>.sgf` | `cfg/<section>.ini` |
| `templates/index.php` | `index.php` |
| `templates/app/src/{{app}}/...` | `app/src/<App>/...` |

`lib/App` and `app/src` prefixes resolve against `rel_app_lib` and `rel_appsrc`
respectively (`Templates::install()`), so a moved application library still
installs correctly.

## Resolution order

[`Templates::path()`](../src/Strukt/Framework/Templates.php) resolves one
template, most specific first:

1. `<root>/templates/<relative>`
2. `<framework>/templates/<relative>`

The loader keeps its existing `rel_loader_sgf` override ahead of both.

```php
Templates::path('lib/App/Middleware/{{class_name}}.sgf'); // project, else framework
Templates::install('lib/App/Middleware/{{class_name}}.php', ['class_name' => 'Audit']);
```

Every generator uses it: `app:make`, `middleware:make`, `provider:make`,
`validator:make`, `bundle:make`, and the loader/front-controller/facet
scaffolding in `Reloader`.

## Scaffolded copies

`app:make` writes the full template set into `<root>/templates/` before
rendering, and `app:reload` backfills any file that is missing. Existing files
are never overwritten, so local edits survive both commands.

```
templates/
├── app/src/{{app}}/...      # application sources rendered by app:make
├── cfg/                     # one template per generated cfg/<section>.ini
│   ├── app.sgf
│   ├── cache.sgf
│   ├── cmd.sgf              # {if bundle_db} guards the optional [bundle-db] group
│   ├── module.sgf
│   └── repo.sgf
├── index.php
├── lib/App/
│   ├── Loader.sgf
│   ├── Middleware/{{class_name}}.sgf
│   ├── Provider/Facet.sgf
│   ├── Provider/{{class_name}}.sgf
│   └── Validator/{{class_name}}.sgf
└── src/Strukt/Bundle/Bundle{{class_name}}.sgf
```

Templates rendered for `cfg/*.ini` receive `app` (the application name) and
`bundle_db` (whether Strukt Ar is installed). `cmd.sgf` uses the latter to add
its `[bundle-db]` command group only when the package that provides those
commands is present.

The skeleton git-ignores `templates/` because it is generated, exactly like
`cfg/`.
