<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Annotation\Parser;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\ProviderInterface;
use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Contract\Validator as ValidatorContract;
use Strukt\Rules\RuleRegistry;

/**
 * Discovers the framework extensions an application ships under `rel_app_lib`.
 *
 * Every class below `lib/App` (namespace `App\`) that implements a known
 * framework extension interface is an *extendable* and is picked up without
 * configuration:
 *
 * - `Strukt\Framework\Contract\Validator` becomes a validation rule, registered
 *   on the shared {@see RuleRegistry} and addressable from a form annotation.
 * - `Strukt\Contract\MiddlewareInterface` joins the middleware pipeline.
 * - `Strukt\Contract\ProviderInterface` is registered with the kernel.
 *
 * The rule name of a validator is its class short name in snake case, or the
 * value of an `@Name` annotation.
 *
 * ```php
 * $extendables = Extendables::discover();
 * $extendables->register(); // wires validators onto the shared rule registry
 * ```
 */
final class Extendables
{
    /** @var array<string, class-string> Validator classes keyed by rule name. */
    private array $validators = [];

    /** @var list<class-string> Middleware classes. */
    private array $middlewares = [];

    /** @var list<class-string> Provider classes. */
    private array $providers = [];

    /** Cached discovery for the last root and library directory. */
    private static ?self $cache = null;
    /** Root the cache was built for. */
    private static ?string $root = null;
    /** Library directory the cache was built for. */
    private static ?string $lib = null;

    private function __construct()
    {
    }

    /**
     * Discovers the extendables under the application library directory.
     *
     * @param string|null $root Project root; defaults to `Env::root_dir`.
     * @return self Discovered extendables.
     */
    public static function discover(?string $root = null): self
    {
        $root = rtrim($root ?? (string) (Env::has('root_dir') ? Env::get('root_dir') : getcwd()), '/\\');
        $lib = trim((string) (Env::has('rel_app_lib') ? Env::get('rel_app_lib') : 'lib/App'), '/\\');

        if (self::$cache !== null && self::$root === $root && self::$lib === $lib) {
            return self::$cache;
        }

        $instance = new self();
        $directory = $lib === '' ? $root : sprintf('%s/%s', $root, $lib);
        $instance->scan($directory, $root);

        self::$root = $root;
        self::$lib = $lib;

        return self::$cache = $instance;
    }

    /**
     * Invokes a callable for every discovered extendable class and its kind.
     *
     * @return void
     */
    public function each(\Closure $callback): void
    {
        foreach ($this->validators as $name => $class) {
            $callback($class, 'validator', $name);
        }

        foreach ($this->middlewares as $class) {
            $callback($class, 'middleware', null);
        }

        foreach ($this->providers as $class) {
            $callback($class, 'provider', null);
        }
    }

    /**
     * Lists discovered validators keyed by rule name.
     *
     * @return array<string, class-string> Validator classes keyed by rule name.
     */
    public function validators(): array
    {
        return $this->validators;
    }

    /**
     * Lists discovered middleware classes.
     *
     * @return list<class-string> Middleware classes.
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Lists discovered provider classes.
     *
     * @return list<class-string> Provider classes.
     */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * Registers the discovered validators on a rule registry.
     *
     * @param RuleRegistry|null $registry Registry to extend, or the shared one.
     * @return self This instance, for chaining.
     */
    public function register(?RuleRegistry $registry = null): self
    {
        $registry ??= RuleRegistry::shared();

        foreach ($this->validators as $name => $class) {
            $registry->extend($name, static fn (string $field, array $params): ValidatorContract => new $class($field, $params));
        }

        return $this;
    }

    /**
     * Clears the cached discovery (testing).
     */
    public static function flush(): void
    {
        self::$cache = null;
        self::$root = null;
        self::$lib = null;
    }

    /**
     * Scans a library directory for extendable classes.
     *
     * @param string $directory Absolute library directory.
     * @param string $root Project root, used to register the `App\` autoloader.
     * @return void
     */
    private function scan(string $directory, string $root): void
    {
        if (!Fs::isDir($directory)) {
            return;
        }

        Autoload::register($root);

        foreach (Fs::lsr($directory) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
                continue;
            }

            $class = $this->classOf($file, $directory);
            if ($class === null || !class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
                continue;
            }

            if ($reflection->implementsInterface(ValidatorContract::class)) {
                $this->validators[$this->ruleName($reflection)] = $class;
                continue;
            }

            if ($reflection->implementsInterface(MiddlewareInterface::class)) {
                $this->middlewares[] = $class;
                continue;
            }

            if ($reflection->implementsInterface(ProviderInterface::class)) {
                $this->providers[] = $class;
            }
        }
    }

    /**
     * Builds the `App\` class name for a file below the library directory.
     *
     * @param string $file Absolute file path.
     * @param string $directory Absolute library directory.
     * @return string|null Class name, or null when it cannot be derived.
     */
    private function classOf(string $file, string $directory): ?string
    {
        $relative = ltrim(substr($file, strlen($directory)), '/\\');
        $relative = (string) preg_replace('/\.php$/', '', $relative);

        if ($relative === '') {
            return null;
        }

        return 'App\\' . str_replace(['/', '\\'], '\\', $relative);
    }

    /**
     * Resolves the rule name of a validator.
     *
     * @param \ReflectionClass $reflection Validator reflection.
     * @return string Rule name.
     */
    private function ruleName(\ReflectionClass $reflection): string
    {
        $notes = (new Parser($reflection))->getNotes()['class'] ?? [];
        $name = isset($notes['Name']['item']) ? (string) $notes['Name']['item'] : $reflection->getShortName();

        return self::snake($name);
    }

    /**
     * Converts a class or annotation name to snake case.
     *
     * @param string $name Name to convert.
     * @return string Snake case name.
     */
    private static function snake(string $name): string
    {
        $name = str_replace(['-', ' '], '_', $name);

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
