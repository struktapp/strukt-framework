# Backlog

Outstanding and planned work for `strukt/framework`. This tracks the gap
between the framework and `strukt1/strukt-framework`, plus follow-ups that
surface during the port. Changes are recorded in [`changelog/`](changelog/README.md)
once they land.

## Console commands still to port

`strukt1/strukt-framework` shipped these commands; they have no `strukt4`
equivalent yet. Command names come from the strukt1 `cfg/cmd.ini`.

| Command | strukt1 source | Notes |
| --- | --- | --- |
| `bundle:publish` | `PackagePublisher.php` | Publish a package into the project: `bundle:publish <pkg> [--dev]`. The live `[pub-pak]` entry. |
| `bundle:copy` | `PackageCopy.php` | Copy the files recorded in `bundle.json` into the project. |
| `bundle:diff` | `PackageDiff.php` | Diff only for package dev-mode: `bundle:diff [<type>]`. Carries a `sebastian/diff` dependency in strukt1. |
| `sys:util` | `SysUtil.php` | Enable or disable console commands, middleware, and providers: `sys:util <enable\|disable> <middleware\|provider\|command> <name>`. |

The package publisher overlaps with the three commands already restored
(`bundle:make`, `bundle:add`, `bundle:export`); see
[`1.3.0-package-publisher-scaffold.md`](changelog/1.3.0-package-publisher-scaffold.md).

## Follow-ups

- **Redesigned `package.ini`.** A per-package variant map was removed in
  [`1.3.0-remove-unused-package-ini.md`](changelog/1.3.0-remove-unused-package-ini.md)
  because nothing read it. If `bundle:publish` / `bundle:copy` / `bundle:diff`
  need per-package variant metadata, reintroduce a purpose-built section (not
  strukt1's `pop-db`/`red-db` copy) as part of that work.

## Conventions

- Every change gets a `changelog/<version>-<slug>.md` entry and an index row
  (see [`AGENTS.md`](../../../AGENTS.md)).
- Verify with `composer test` and `composer lint` before closing an item.
