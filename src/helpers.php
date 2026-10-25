<?php

use Strukt\Container\Container;
use Strukt\Framework\Configuration;
use Strukt\Framework\Bundle\BundlesRepository;
use Strukt\Framework\Contract\BundlesRepositoryInterface;
use Strukt\Framework\Contract\Kernel;
use Strukt\Framework\Contract\ModuleStore;
use Strukt\Framework\Module\Registrar;
use Strukt\Framework\Module\Storage\RegistryStore;
use Strukt\Framework\Router\RouterAdapter;

if (function_exists('helper')) {
    helper('framework');
}

/*
 * Load the framework configuration sections into the shared config registry.
 *
 * Sections are only written when absent so an application can override any of
 * them before the framework loads.
 */
if (function_exists('config')) {
    foreach (glob(dirname(__DIR__) . '/cfg/*.ini') ?: [] as $config_file) {
        $section = pathinfo($config_file, PATHINFO_FILENAME);
        if (config($section) !== null) {
            continue;
        }

        $parsed = parse_ini_file($config_file, true, INI_SCANNER_TYPED);
        if ($parsed !== false) {
            config($section, $parsed);
        }
    }
}

if (!function_exists('repos') && function_exists('helper_add') && helper_add('repos')) {
    /**
     * Lists the configured framework bundles.
     *
     * @param string|null $type Optional status filter: `installed` or `published`.
     * @return array<string, string>|list<string> Bundle classes keyed by name, or names when filtered.
     */
    function repos(?string $type = null): array
    {
        return $type === null
            ? container()->get(BundlesRepositoryInterface::class)->available()
            : container()->get(BundlesRepositoryInterface::class)->list($type);
    }
}

if (!function_exists('container') && function_exists('helper_add') && helper_add('container')) {
    /**
     * Returns the shared dependency container.
     *
     * ```php
     * $kernel = container()->get(Kernel::class);
     * ```
     *
     * @param Container|null $container Container to install as the shared instance.
     * @return Container The shared container.
     */
    function container(?Container $container = null): Container
    {
        if ($container instanceof Container) {
            Container::setInstance($container);
        }

        return Container::getInstance();
    }

    $container = container();
    $container->instance(ModuleStore::class, new RegistryStore());
    $container->instance(BundlesRepositoryInterface::class, new BundlesRepository());
    $container->factory(Registrar::class, static fn (Container $c): Registrar => new Registrar(
        $c->get(ModuleStore::class),
        (array) config('module.folders') ?: [],
    ));
    $container->set(Configuration::class, static fn (Container $c): Configuration => new Configuration(
        cache('app'),
        (string) (config('app.type') ?? 'App:Idx'),
        $c->get(BundlesRepositoryInterface::class),
    ));
    $container->set(Kernel::class, static fn (): RouterAdapter => new RouterAdapter(router()));

    if (class_exists(\Strukt\Rules\RuleRegistry::class)) {
        $container->instance(\Strukt\Rules\RuleRegistry::class, \Strukt\Rules\RuleRegistry::shared());
    }
}

if (!function_exists('core') && function_exists('helper_add') && helper_add('core')) {
    /**
     * Resolves an application facet by module alias.
     *
     * @param string $alias Module alias such as `au.ctr.User`.
     * @param list<mixed>|null $args Constructor arguments.
     * @return object Resolved facet.
     */
    function core(string $alias, ?array $args = null): object
    {
        $provider = event('provider.core');
        if ($provider === null) {
            raise('The [provider.core] provider is not registered.');
        }

        return $provider->apply($alias, $args)->exec();
    }
}

if (!function_exists('bundle') && function_exists('helper_add') && helper_add('bundle')) {
    /**
     * Queries the metadata of a configured bundle.
     *
     * ```php
     * bundle('core')->get('requirements');
     * ```
     *
     * @param string $name Bundle name, such as `core`.
     * @param string $mode Application type, `App:Cli` or `App:Idx`.
     * @return object Adapter exposing `get(string $which)`.
     *
     * @throws \Strukt\Raise When the bundle or mode is invalid.
     */
    function bundle(string $name, string $mode = 'App:Cli'): object
    {
        $repos = repos();
        if (!array_key_exists($name, $repos)) {
            raise(sprintf('Bundle %s does not exist!', $name));
        }

        if (!in_array($mode, ['App:Cli', 'App:Idx'], true)) {
            raise('Invalid bundle mode!');
        }

        return new class($repos[$name], $mode) {
            /** @var \Strukt\Framework\Contract\Bundle Bundle instance. */
            private object $meta;

            /** Application type. */
            private string $mode;

            /**
             * @param class-string $class Bundle class.
             * @param string $mode Application type.
             */
            public function __construct(string $class, string $mode)
            {
                $this->meta = container()->make($class);
                $this->mode = $mode;
            }

            /**
             * Reads one facet of the bundle manifest.
             *
             * @param string $which Field or alias to read.
             * @return array<mixed>|string|bool|null Manifest value.
             */
            public function get(string $which): array|string|bool|null
            {
                $settings = $this->meta->getSettings($this->mode);

                return match ($which) {
                    'providers', 'provider', 'prv' => $settings['providers'] ?? [],
                    'middlewares', 'middleware', 'mdl' => $settings['middlewares'] ?? [],
                    'commands', 'command', 'cmd' => $settings['commands'] ?? [],
                    'settings', 'config', 'cfg' => $settings,
                    'name' => $this->meta->getName(),
                    'cmd:name' => $this->meta->getCmdName(),
                    'files' => $this->meta->getFiles(),
                    'modules', 'mods', 'mod' => $this->meta->getModules(),
                    'is:published', 'is:pub', 'pub' => $this->meta->isPublished(),
                    'requirements', 'reqs', 'req' => $this->meta->getRequirements(),
                    default => null,
                };
            }
        };
    }
}

if (!function_exists('hashfn') && function_exists('helper_add') && helper_add('hashfn')) {
    /**
     * Returns a password hashing callable.
     *
     * @param string|null $type Hash algorithm, such as `sha1`. Defaults to `sha1`.
     * @return callable(string): string Hashing callable.
     */
    function hashfn(?string $type = null): callable
    {
        $type = strtolower($type ?? 'sha1');

        if (in_array($type, hash_algos(), true)) {
            return static fn (string $password): string => hash($type, $password);
        }

        return static fn (string $password): string => sha1($password);
    }
}
