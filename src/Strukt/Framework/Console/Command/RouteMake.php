<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Env;
use Strukt\Framework\Autoload;
use Strukt\Fs\Fs;
use Strukt\Raise;

/**
 * @command route:make
 * @description Generate or extend a module router
 * @usage route:make [<module>] [<name>] [<route>] [--method=GET] [--permission=] [--function=]
 * @argument module
 * @description Module alias, such as "au" (prompted when omitted)
 * @argument name
 * @description Router class name (prompted when omitted)
 * @argument route
 * @description Route path, such as "/user/all" (prompted when omitted)
 * @option method
 * @description HTTP method
 * @type string
 * @default GET
 * @enum GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD
 * @option permission
 * @description Permission required to reach the route
 * @type string
 * @option function
 * @description Action method name
 * @type string
 */
final class RouteMake implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $root = rtrim((string) Env::get('root_dir'), '/');
        $appSrc = trim((string) Env::get('rel_appsrc'), '/');

        $modules = self::discover($root, $appSrc);

        $alias = self::resolveModule($arguments['module'] ?? null, $modules);
        $name = self::resolveName($arguments['name'] ?? null);
        $route = self::resolveRoute($arguments['route'] ?? null);

        $method = strtoupper((string) ($options['method'] ?? 'GET'));
        $permission = trim((string) ($options['permission'] ?? ''));
        $function = trim((string) ($options['function'] ?? ''));

        $module = $modules[$alias];
        $folder = (string) (((array) config('module.folders'))['rtr'] ?? 'Router');

        $namespace = sprintf('%s\\%s', $module['base_ns'], $folder);
        $directory = sprintf('%s/%s', $module['dir'], $folder);
        $path = sprintf('%s/%s.php', $directory, $name);

        $existing = Fs::isFile($path) ? (string) Fs::cat($path) : '';
        $base = $function !== '' ? lcfirst(self::camel($function)) : self::routeName($route);
        $function = self::uniqueName($base, $existing);

        $block = self::method($function, $route, $method, $permission);

        if ($existing === '') {
            $output = self::header($namespace, $name) . "\n" . $block . "}\n";
            Fs::mkdir($directory);
            Fs::touchWrite($path, $output);
            echo "Router created.\n";

            return 0;
        }

        $output = self::append($existing, $block);
        if (Fs::overwrite($path, $output) === false) {
            throw new Raise(sprintf('Could not write [%s].', $path));
        }

        echo sprintf("Route added to %s.\n", $name);

        return 0;
    }

    /**
     * Discovers the application modules keyed by alias.
     *
     * @param string $root Project root.
     * @param string $appSrc Application source directory.
     * @return array<string, array{base_ns: string, dir: string}> Module metadata by alias.
     */
    private static function discover(string $root, string $appSrc): array
    {
        Autoload::register($root);

        $modules = [];
        $appSrcAbs = sprintf('%s/%s', $root, $appSrc);

        foreach (glob($appSrcAbs . '/*', GLOB_ONLYDIR) ?: [] as $appDir) {
            $app = basename($appDir);
            foreach (glob($appDir . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
                $module = basename($moduleDir);
                if (preg_match('/[A-Za-z]+Module$/', $module) !== 1) {
                    continue;
                }

                $class = sprintf('%s\\%s\\%s%s', $app, $module, $app, $module);
                if (!class_exists($class)) {
                    continue;
                }

                $instance = new $class();
                $alias = strtolower($instance->getAlias());
                if ($alias === '') {
                    continue;
                }

                $namespace = $instance->getNamespace();
                $base_ns = substr($namespace, 0, (int) strrpos($namespace, '\\'));

                $modules[$alias] = [
                    'base_ns' => $base_ns,
                    'dir' => $instance->getBaseDir(),
                ];
            }
        }

        ksort($modules);

        return $modules;
    }

    /**
     * Resolves the module alias from the argument or an interactive prompt.
     *
     * @param mixed $module Provided module argument.
     * @param array<string, array{base_ns: string, dir: string}> $modules Discovered modules.
     * @return string Selected module alias.
     */
    private static function resolveModule(mixed $module, array $modules): string
    {
        if ($modules === []) {
            throw new Raise('No modules found. Run app:make and app:reload first.');
        }

        $alias = strtolower(trim((string) $module));
        if ($alias !== '') {
            if (!isset($modules[$alias])) {
                throw new Raise(sprintf('Unknown module [%s]. Available: %s.', $alias, implode(', ', array_keys($modules))));
            }

            return $alias;
        }

        self::requireInteractive('module');

        $aliases = array_keys($modules);
        echo "Modules:\n";
        foreach ($aliases as $index => $candidate) {
            echo sprintf("  %d. %s\n", $index + 1, $candidate);
        }

        $answer = self::ask('Select Module: ');
        $selected = (int) $answer - 1;
        if (!isset($aliases[$selected])) {
            throw new Raise(sprintf('Invalid module selection [%s].', $answer));
        }

        return $aliases[$selected];
    }

    /**
     * Resolves the router name from the argument or an interactive prompt.
     *
     * @param mixed $name Provided name argument.
     * @return string Router class name.
     */
    private static function resolveName(mixed $name): string
    {
        $resolved = trim((string) $name);
        if ($resolved === '') {
            self::requireInteractive('name');
            $resolved = self::ask('Router Name: ');
        }

        $resolved = (string) preg_replace('/[Rr]outer$/', '', $resolved);
        $resolved = self::camel($resolved);
        if ($resolved === '') {
            throw new Raise('A router name is required.');
        }

        return $resolved;
    }

    /**
     * Resolves the route path from the argument or an interactive prompt.
     *
     * @param mixed $route Provided route argument.
     * @return string Normalized route path.
     */
    private static function resolveRoute(mixed $route): string
    {
        $resolved = trim((string) $route);
        if ($resolved === '') {
            self::requireInteractive('route');
            $resolved = self::ask(' Route: ');
        }

        return sprintf('/%s', trim($resolved, '/'));
    }

    /**
     * Renders the class header for a new router file.
     *
     * @param string $namespace Router namespace.
     * @param string $name Router class name.
     * @return string Class source without a closing brace.
     */
    private static function header(string $namespace, string $name): string
    {
        return sprintf(
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace %s;\n\nuse Strukt\\Contract\\Http\\RequestInterface;\nuse Strukt\\Framework\\Contract\\Router;\n\nfinal class %s extends Router\n{",
            $namespace,
            $name,
        );
    }

    /**
     * Renders one annotated route method.
     *
     * @param string $function Action name.
     * @param string $route Route path.
     * @param string $method HTTP method.
     * @param string $permission Optional permission.
     * @return string Method source block.
     */
    private static function method(string $function, string $route, string $method, string $permission): string
    {
        $params = ['RequestInterface $request'];
        foreach (self::placeholders($route) as $placeholder) {
            $params[] = sprintf('%s $%s', $placeholder['type'], $placeholder['name']);
        }

        $annotations = [
            sprintf('     * @Method(%s)', $method),
            sprintf('     * @Route(%s)', $route),
        ];

        if ($permission !== '') {
            $annotations[] = sprintf('     * @Permission(%s)', $permission);
        }

        return sprintf(
            "    /**\n%s\n     */\n    public function %s(%s)\n    {\n        //\n    }\n",
            implode("\n", $annotations),
            $function,
            implode(', ', $params),
        );
    }

    /**
     * Inserts a method block into an existing router class.
     *
     * @param string $existing Existing class source.
     * @param string $block Rendered method block.
     * @return string Updated class source.
     */
    private static function append(string $existing, string $block): string
    {
        $existing = self::ensureImport($existing, 'use Strukt\\Contract\\Http\\RequestInterface;');
        $position = strrpos($existing, '}');
        if ($position === false) {
            throw new Raise('The router file is malformed.');
        }

        return sprintf(
            "%s\n\n%s}\n",
            rtrim(substr($existing, 0, $position), "\n"),
            $block,
        );
    }

    /**
     * Ensures a `use` import is present, inserting it after the last import.
     *
     * @param string $source Existing class source.
     * @param string $import Import statement to add.
     * @return string Updated class source.
     */
    private static function ensureImport(string $source, string $import): string
    {
        if (str_contains($source, $import)) {
            return $source;
        }

        if (preg_match_all('/^use [^\n]+;\n/m', $source, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $last = end($matches[0]);
            $offset = $last[1] + strlen($last[0]);

            return substr($source, 0, $offset) . $import . "\n" . substr($source, $offset);
        }

        if (preg_match('/^namespace [^\n]+;\n/m', $source, $match, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $match[0][1] + strlen($match[0][0]);

            return substr($source, 0, $offset) . "\n" . $import . "\n" . substr($source, $offset);
        }

        return $source;
    }

    /**
     * Extracts the typed placeholders from a route pattern.
     *
     * @param string $route Route path.
     * @return list<array{name: string, type: string}> Placeholders in order.
     */
    private static function placeholders(string $route): array
    {
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)(?::([A-Za-z]+))?\}/', $route, $matches, PREG_SET_ORDER);

        $placeholders = [];
        foreach ($matches as $match) {
            $placeholders[] = [
                'name' => $match[1],
                'type' => self::phpType($match[2] ?? 'string'),
            ];
        }

        return $placeholders;
    }

    /**
     * Maps a route placeholder type to a PHP scalar type.
     *
     * @param string $type Route placeholder type.
     * @return string PHP type name.
     */
    private static function phpType(string $type): string
    {
        return match (strtolower($type)) {
            'int', 'integer' => 'int',
            'numeric', 'float' => 'float',
            'bool', 'boolean' => 'bool',
            default => 'string',
        };
    }

    /**
     * Derives an action method name from a route path.
     *
     * @param string $route Route path.
     * @return string Method name.
     */
    private static function routeName(string $route): string
    {
        $segments = array_values(array_filter(
            explode('/', trim($route, '/')),
            static fn (string $segment): bool => $segment !== '' && !str_starts_with($segment, '{'),
        ));

        if ($segments === []) {
            return 'index';
        }

        $identifier = preg_replace('/[^A-Za-z0-9]+/', ' ', implode(' ', $segments));

        return lcfirst(self::camel((string) $identifier));
    }

    /**
     * Makes a method name unique within the existing class source.
     *
     * @param string $base Base method name.
     * @param string $existing Existing class source.
     * @return string Method name free of collisions.
     */
    private static function uniqueName(string $base, string $existing): string
    {
        $name = $base === '' ? 'index' : $base;
        $suffix = 1;
        while (self::hasMethod($existing, $name)) {
            $name = $base . ++$suffix;
        }

        return $name;
    }

    /**
     * Checks whether a class source already declares a method.
     *
     * @param string $source Class source.
     * @param string $name Method name.
     * @return bool True when the method exists.
     */
    private static function hasMethod(string $source, string $name): bool
    {
        return $source !== '' && preg_match(sprintf('/function\s+%s\s*\(/', preg_quote($name, '/')), $source) === 1;
    }

    /**
     * Prompts for a value on an interactive terminal.
     *
     * @param string $label Prompt text.
     * @return string Trimmed answer.
     */
    private static function ask(string $label): string
    {
        fwrite(STDOUT, $label);
        $answer = fgets(STDIN);
        if ($answer === false) {
            throw new Raise('Unable to read interactive input.');
        }

        return trim($answer);
    }

    /**
     * Fails when an argument is missing outside an interactive terminal.
     *
     * @param string $argument Missing argument name.
     * @return void
     */
    private static function requireInteractive(string $argument): void
    {
        if (function_exists('stream_isatty') && @stream_isatty(STDIN)) {
            return;
        }

        throw new Raise(sprintf('Argument <%s> is required when not running interactively.', $argument));
    }

    /**
     * Converts an underscored or spaced value to Pascal case.
     *
     * @param string $value Input value.
     * @return string Pascal-cased value.
     */
    private static function camel(string $value): string
    {
        return implode('', array_map('ucfirst', preg_split('/[_\s-]+/', $value) ?: [])) ?: '';
    }
}
