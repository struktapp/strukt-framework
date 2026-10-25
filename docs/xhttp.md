# xhttp

`xhttp` is a development-only, in-process HTTP client. It builds a request from
the command line, boots your application, dispatches the request through the
application's own kernel, and prints the response. It lets you exercise a route
from the terminal without starting a web server.

It is a **development tool**: the binary refuses to run unless `is_dev` is true.

## Why

Reaching a route normally means starting the built-in server and reaching for
`curl`, an HTTP client, or a browser. `xhttp` skips the network entirely and runs
the application in the current process, so you get the real kernel, middleware,
routing, and providers — but the response lands in your terminal.

Session values and credentials live in [`cfg/xhttp.json`](#profiles), never on the
command line, so secrets stay out of your shell history.

## Usage

Composer publishes `bin/xhttp` to `vendor/bin/xhttp`. Run it from your project
root, the same way as [`strukt`](console.md):

```bash
vendor/bin/xhttp [METHOD] <path> [key=value ...] [--header "Name: Value"] [--as <profile>] [--json]
```

| Argument / option | Meaning |
| --- | --- |
| `METHOD` | `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`, `HEAD`. Default: `GET`. |
| `<path>` | Request path, e.g. `/login` or `/user/7`. Default: `/`. |
| `key=value` | For `GET`/`HEAD`, query-string parameters. Otherwise, request body fields. Secrets are refused here. |
| `--header "Name: Value"` | Add a request header. Repeatable. |
| `--as <profile>` | Use a profile from [`cfg/xhttp.json`](#profiles): seed its session and merge its data into the request fields. |
| `--json` | Pretty-print a JSON response body. |
| `-h`, `--help` | Show usage. Always available, even when `is_dev` is off. |

## Profiles

A profile is a named entry in `cfg/xhttp.json` that carries the `session` values
to seed before dispatch and the `data` fields to merge into the request. This is
how you reach session-guarded routes and log in without typing credentials:

```json
{
    "admin": {
        "session": { "user.id": 1, "user.username": "admin", "user.email": "admin@strukt.test" },
        "data": { "email": "admin@strukt.test", "password": "secret" }
    }
}
```

- `session` is written into the router session before dispatch, so routes behind
  the `sess`/`auth` middleware resolve an authenticated user. The keys follow the
  `strukt/auth` convention (`user.id`, `user.username`, `user.email`,
  `user.token`).
- `data` is merged into the request fields — query for `GET`/`HEAD`, body
  otherwise — the same as a `key=value` argument, but without exposing it on the
  command line. Arguments passed explicitly on the command line override `data`.

Select a profile with `--as`; it is resolved from `<project root>/cfg/xhttp.json`.

## Examples

```bash
# A simple GET
vendor/bin/xhttp GET /hello/ada

# Query parameters
vendor/bin/xhttp GET /search q=ada page=2

# Log in using a stored profile (credentials come from cfg/xhttp.json)
vendor/bin/xhttp POST /login --as admin

# Reach an authenticated route as the profile's session user
vendor/bin/xhttp GET /admin/users --as admin

# Send a header and pretty-print the JSON response
vendor/bin/xhttp GET /api/users --header "Accept: application/json" --json
```

The response is printed as a status line, the response headers, and the body:

```
GET /hello/ada -> 200

Hello ada!
```

The exit code is `0` for a response below `400` and `1` otherwise, so `xhttp`
composes in scripts.

## How it works

1. The binary requires `vendor/autoload.php` from the current working directory,
   loads `.env`, sets the framework defaults, and rejects the run unless `is_dev`
   is true.
2. It parses the arguments. If `--as` is given, the named profile is read from
   `cfg/xhttp.json`; its `data` is merged into the request fields and its
   `session` is queued for seeding.
3. It registers the application autoloader and detects the generated
   `App\Loader`, falling back to `Application::make()`.
4. `xhttp` calls `$app->init()` on the **current global router**, seeds the
   session if the profile supplied one, then dispatches through
   `router()->kernel()->handle()`.
5. The response is rendered to the terminal and its status decides the exit code.

## Limitations

- It never opens a socket: it is not a substitute for testing through a real HTTP
  server, TLS, cookies on the wire, or client-side redirects.
- It is enabled only when `is_dev` is true and is intended for local use.
- Common secret field names (`password`, `passwd`, `pwd`, `pass`, `secret`) are
  refused as command-line arguments; put them in a profile instead.
- One request per invocation.

## Programmatic use

```php
use Strukt\Framework\Console\Xhttp;

$profile = Xhttp::profile('admin', getcwd() . '/cfg/xhttp.json');
$xhttp = new Xhttp('POST', '/login', $profile['data'], [], $profile['session']);
$response = $xhttp->run();

echo $xhttp->render($response, pretty: true);
```

`Xhttp::profile(string $name, string $file)` returns `['session' => [...], 'data' => [...]]`
and raises when the file or profile is missing. `Xhttp::help()` returns the usage
text shown for `--help`.
