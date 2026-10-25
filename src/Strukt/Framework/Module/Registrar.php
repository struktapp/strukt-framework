<?php

declare(strict_types=1);

namespace Strukt\Framework\Module;

use Strukt\Framework\Contract\Module;
use Strukt\Framework\Contract\ModuleStore;
use Strukt\Framework\Module\Storage\RegistryStore;
use Strukt\Fs\Fs;

/**
 * Discovers the facets of a module and indexes them in a module store.
 *
 * The registrar owns every module side effect: the deduplication by namespace,
 * the alias map, and the `nr.<alias>.<facet>.<class>` / `nr.routes.<alias>`
 * entries that {@see \Strukt\Framework\Application} later consumes.
 *
 * The registry and the facet folder map are injected through the constructor,
 * falling back to the shared globals when not provided:
 *
 * ```php
 * $registrar = new Registrar(new MemoryStore(), ['ctr' => 'Controller']);
 * ```
 */
final class Registrar
{
    /** @var array<string, string> Registered modules keyed by alias. */
    private array $modules = [];

    /** @var list<string> Registered module aliases, in order. */
    private array $aliases = [];

    /** @var array<string, string> Facet key to folder name. */
    private array $folders;

    /** @var array<string, string> Module route prefixes keyed by alias. */
    private array $prefixes = [];

    /**
     * @param ModuleStore|null $store Registry to write through.
     * @param array<string, string>|null $folders Facet key to folder name map.
     */
    public function __construct(private ?ModuleStore $store = null, ?array $folders = null)
    {
        $this->store ??= new RegistryStore();
        $this->folders = array_flip((array) ($folders ?? config('module.folders') ?: []));
    }

    /**
     * Registers a module and indexes its facets.
     *
     * @param Module $module Module to register.
     * @return void
     */
    public function register(Module $module): void
    {
        $namespace = $module->getNamespace();
        if (in_array($namespace, $this->modules, true)) {
            return;
        }

        $alias = strtolower($module->getAlias());
        $directory = $module->getBaseDir();

        $this->modules[$alias] = $namespace;
        $this->aliases[] = $alias;

        $prefix = '';
        if (property_exists($module, 'prefix')) {
            $value = $module->prefix;
            if (is_string($value)) {
                $prefix = $value;
            }
        } elseif (method_exists($module, 'prefix')) {
            $value = $module->prefix();
            if (is_string($value)) {
                $prefix = $value;
            }
        } elseif (method_exists($module, 'getPrefix')) {
            $value = $module->getPrefix();
            if (is_string($value)) {
                $prefix = $value;
            }
        }
        if ($prefix !== '') {
            $prefix = trim($prefix, '/');
            $prefix = $prefix === '' ? '' : '/' . $prefix;
            $this->prefixes[$alias] = $prefix;
            if (!$this->store->exists(sprintf('nr.prefix.%s', $alias))) {
                $this->store->set(sprintf('nr.prefix.%s', $alias), $prefix);
            }
        }

        $facets = $this->folders;
        $module_class = $this->moduleClass($namespace);
        $base_ns = substr($namespace, 0, strrpos($namespace, '\\')) ?: $namespace;

        $routers = [];
        foreach (Fs::lsr($directory) as $file) {
            $base_path = trim(str_replace([$directory, '.php'], '', $file), '/');
            if ($base_path === $module_class || $base_path === '') {
                continue;
            }

            $segments = explode('/', $base_path);
            $facet = $segments[0];
            $name = $segments[1] ?? '';

            if (!array_key_exists($facet, $facets)) {
                continue;
            }

            $facet_ns = sprintf('%s\\%s', $base_ns, str_replace('/', '\\', $base_path));
            $key = sprintf('nr.%s.%s.%s', $alias, $facets[$facet], $name);

            if (!$this->store->exists($key)) {
                $this->store->set($key, $facet_ns);
            }

            if ($facet === 'Router') {
                $routers[] = $facet_ns;
            }
        }

        $this->store->set(sprintf('nr.routes.%s', $alias), array_values($routers));
    }

    /**
     * Returns the registered modules keyed by alias.
     *
     * @return array<string, string> Module namespaces keyed by alias.
     */
    public function modules(): array
    {
        return $this->modules;
    }

    /**
     * Returns the registered module aliases, in order.
     *
     * @return list<string> Module aliases.
     */
    public function aliases(): array
    {
        return $this->aliases;
    }

    /**
     * Returns the route prefix configured for a module alias.
     *
     * @param string $alias Module alias.
     * @return string Normalized prefix, or empty string.
     */
    public function prefix(string $alias): string
    {
        if (array_key_exists($alias, $this->prefixes)) {
            return $this->prefixes[$alias];
        }

        $stored = $this->store->get(sprintf('nr.prefix.%s', $alias));

        return is_string($stored) ? $stored : '';
    }

    /**
     * Returns the router classes registered for one module alias.
     *
     * @param string $alias Module alias.
     * @return list<string> Router class names.
     */
    public function routes(string $alias): array
    {
        return array_values((array) $this->store->get(sprintf('nr.routes.%s', $alias)) ?: []);
    }

    /**
     * Derives the module class short name from its namespace.
     *
     * @param string $namespace Fully qualified module class name.
     * @return string Module class short name.
     */
    private function moduleClass(string $namespace): string
    {
        $parts = explode('\\', $namespace);

        return (string) end($parts);
    }
}