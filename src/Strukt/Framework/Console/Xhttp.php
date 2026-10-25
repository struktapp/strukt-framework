<?php

declare(strict_types=1);

namespace Strukt\Framework\Console;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Framework\Application;
use Strukt\Framework\Autoload;
use Strukt\Http\Request;

/**
 * Development-only in-process HTTP client.
 *
 * Builds a request from CLI input, boots the application, and dispatches it
 * through the application's own kernel, so a route can be exercised from the
 * terminal without a web server. It is meant for local development only and is
 * gated by the `is_dev` environment flag at the binary entry point.
 *
 * ```php
 * $response = (new Xhttp('POST', '/login', ['email' => 'a@b.c', 'password' => 'x']))->run();
 * echo (new Xhttp('GET', '/profile'))->render($response);
 * ```
 */
final class Xhttp
{
    /**
     * @param string $method HTTP method, uppercased on construction.
     * @param string $path Request path, optionally carrying a query string.
     * @param array<string, mixed> $params Query (GET/HEAD) or body parameters.
     * @param array<string, string> $headers Request headers.
     * @param array<string, mixed> $session Session values seeded before dispatch,
     *                                        typically supplied by a config profile.
     */
    public function __construct(
        private string $method = 'GET',
        private string $path = '/',
        private array $params = [],
        private array $headers = [],
        private array $session = [],
    ) {
        $this->method = strtoupper($this->method);
        $this->path = $this->path === '' ? '/' : $this->path;
    }

    /**
     * Builds the request from the configured method, path, and parameters.
     *
     * Query-string parameters are folded into the URI for `GET`/`HEAD`; every
     * other method carries them as the request body instead.
     *
     * @return RequestInterface Request ready for dispatch.
     */
    public function request(): RequestInterface
    {
        $path = $this->path;
        $body = [];

        if (in_array($this->method, ['GET', 'HEAD'], true)) {
            if ($this->params !== []) {
                $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($this->params);
            }
        } else {
            $body = $this->params;
        }

        return new Request($this->method, $path, [], $body, $this->headers);
    }

    /**
     * Boots the application, dispatches the request, and returns the response.
     *
     * @param RequestInterface|null $request Request to dispatch, or one built
     *                                       from the configured input.
     * @return ResponseInterface Resulting response.
     */
    public function run(?RequestInterface $request = null): ResponseInterface
    {
        Autoload::register();

        $router = router();

        $loader = class_exists(\App\Loader::class) ? new \App\Loader($router) : null;
        $app = $loader !== null && method_exists($loader, 'getApp')
            ? $loader->getApp()
            : Application::make($router);

        $app->init();
        $this->seedSession();

        return router()->kernel()->handle($request ?? $this->request());
    }

    /**
     * Renders a response for the terminal.
     *
     * @param ResponseInterface $response Response to render.
     * @param bool $pretty Pretty-print a JSON body when it decodes.
     * @return string Human-readable status line, headers, and body.
     */
    public function render(ResponseInterface $response, bool $pretty = false): string
    {
        $lines = [sprintf('%s %s -> %d', $this->method, $this->path, $response->status())];

        foreach ((array) $response->header() as $name => $values) {
            $label = implode('-', array_map('ucfirst', explode('-', (string) $name)));
            foreach ((array) $values as $value) {
                $lines[] = sprintf('%s: %s', $label, $value);
            }
        }

        $body = $response->body();
        if ($pretty) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        }

        return implode("\n", $lines) . "\n\n" . $body;
    }

    /**
     * Seeds and pins the router session when session values were supplied.
     *
     * The application's own store is resolved once, filled, then bound back as
     * the factory so the dispatch pipeline reuses the seeded instance.
     *
     * @return void
     */
    private function seedSession(): void
    {
        if ($this->session === []) {
            return;
        }

        $router = router();
        $store = $router->resolveSession();

        foreach ($this->session as $key => $value) {
            $store->set((string) $key, $value);
        }

        $router->session(static fn () => $store);
    }

    /**
     * Loads a named profile from a JSON config file.
     *
     * A profile keeps session values and secrets such as passwords out of the
     * command line. `session` is seeded before dispatch; `data` is merged into
     * the request fields (query for `GET`/`HEAD`, body otherwise).
     *
     * ```json
     * {
     *   "admin": {
     *     "session": { "user.id": 1, "user.username": "admin" },
     *     "data": { "email": "admin@strukt.test", "password": "secret" }
     *   }
     * }
     * ```
     *
     * @param string $name Profile name.
     * @param string $file Path to the JSON config file.
     * @return array{session: array<string, mixed>, data: array<string, mixed>}
     *
     * @throws \Strukt\Raise When the file is missing, malformed, or the profile
     *                       is absent or not an object.
     */
    public static function profile(string $name, string $file): array
    {
        if (!is_file($file)) {
            raise(sprintf('xhttp config [%s] not found. Create it to use --as.', $file));
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (!is_array($decoded)) {
            raise(sprintf('xhttp config [%s] is not valid JSON.', $file));
        }

        if (!array_key_exists($name, $decoded)) {
            raise(sprintf('Profile [%s] not found in [%s].', $name, $file));
        }

        if (!is_array($decoded[$name])) {
            raise(sprintf('Profile [%s] in [%s] must be an object.', $name, $file));
        }

        return [
            'session' => (array) ($decoded[$name]['session'] ?? []),
            'data' => (array) ($decoded[$name]['data'] ?? []),
        ];
    }

    /**
     * Usage text shown for `--help`.
     *
     * @return string Multi-line help.
     */
    public static function help(): string
    {
        return <<<'TXT'
        xhttp — development-only in-process HTTP client

        Usage:
          xhttp [METHOD] <path> [key=value ...] [--header "Name: Value"] [--as <profile>] [--json]

        Arguments:
          METHOD           HTTP method (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD). Default: GET.
          path             Request path, e.g. /login or /user/7. Default: /.
          key=value        GET/HEAD: query parameters. Otherwise: request body fields.

        Options:
          --header <H>     Add a request header, e.g. --header "Accept: application/json".
          --as <profile>   Use a profile from cfg/xhttp.json: seed its session and merge its data.
          --json           Pretty-print a JSON response body.
          -h, --help       Show this help.

        Profiles:
          Session values and credentials live in cfg/xhttp.json, never on the command line:

          {
              "admin": {
                  "session": { "user.id": 1, "user.username": "admin" },
                  "data": { "email": "admin@strukt.test", "password": "secret" }
              }
          }

        Examples:
          xhttp GET /hello/ada
          xhttp GET /search q=ada page=2
          xhttp POST /login --as admin
          xhttp GET /admin/users --as admin --json

        This tool runs an application in-process and is disabled unless is_dev is true.
        TXT;
    }
}
