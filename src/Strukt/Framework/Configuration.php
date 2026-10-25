<?php

declare(strict_types=1);

namespace Strukt\Framework;

use Strukt\Cache\Cache;
use Strukt\Framework\Bundle\BundlesRepository;
use Strukt\Framework\Contract\BundlesRepositoryInterface;
use Strukt\Framework\Injectable\Configuration as InjectableConfiguration;
use Strukt\Framework\Traits\ClassHelper;
use Strukt\Framework\Traits\InjectablesHelper;

/**
 * Assembles the providers, middlewares, and commands of an application.
 *
 * Every published bundle contributes settings for the active application type
 * (`App:Idx` or `App:Cli`). The aggregated result is cached per type so the
 * discovery cost is paid once, and is exposed through {@see self::get()}:
 *
 * ```php
 * $configuration = new Configuration();
 * $providers = $configuration->get('providers');
 * ```
 *
 * The cache, application type, and bundle repository are injected through the
 * constructor, falling back to the shared globals when not provided.
 */
final class Configuration
{
    use ClassHelper;
    use InjectablesHelper;

    /** @var array<string, mixed> Aggregated settings. */
    private array $settings;

    /** @var Cache Cache holding the aggregated settings. */
    private Cache $cache;

    /** @var string Application type, such as `App:Idx` or `App:Cli`. */
    private string $appType;

    /** @var BundlesRepositoryInterface Bundle discovery. */
    private BundlesRepositoryInterface $bundles;

    /**
     * @param Cache|null $cache Cache holding the settings, or the shared `app` cache.
     * @param string|null $appType Application type, or the `app.type` value.
     * @param BundlesRepositoryInterface|null $bundles Bundle discovery, or the static registry.
     */
    public function __construct(
        ?Cache $cache = null,
        ?string $appType = null,
        ?BundlesRepositoryInterface $bundles = null,
    ) {
        $this->cache = $cache ?? cache('app');
        $this->appType = $appType ?? (string) (config('app.type') ?? 'App:Idx');
        $this->bundles = $bundles ?? new BundlesRepository();
        $this->settings = $this->load();
    }

    /**
     * Loads the settings, discovering them once and caching per application type.
     *
     * @return array<string, mixed> Aggregated settings.
     */
    private function load(): array
    {
        if ($this->cache->empty() || !$this->cache->exists($this->appType)) {
            $this->cache->put($this->appType, self::create($this->appType, $this->bundles))->save();
        }

        return (array) $this->cache->get($this->appType);
    }

    /**
    /**
     * Discovers the settings contributed by every published bundle.
     *
     * @param string $app_type Application type, such as `App:Idx` or `App:Cli`.
     * @param BundlesRepositoryInterface|null $bundles Bundle discovery, or the static registry.
     * @return array{providers: list<string>, middlewares: list<string>, commands: list<string>, aliases: array<string, list<string>>} Settings.
     */
    public static function create(string $app_type, ?BundlesRepositoryInterface $bundles = null): array
    {
        $helper = new class {
            use ClassHelper;
            use InjectablesHelper;
        };

        $bundles ??= new BundlesRepository();
        $published = $bundles->list('published');
        $settings = [
            'providers' => [],
            'middlewares' => [],
            'commands' => [],
            'aliases' => ['middlewares' => [], 'providers' => []],
        ];

        foreach ($bundles->available() as $name => $class) {
            if (!in_array($name, $published, true) || !class_exists($class)) {
                continue;
            }

            $config = $helper->newClass($class)->getSettings($app_type);

            foreach (['middlewares', 'providers'] as $facet) {
                foreach (array_values($config[$facet] ?? []) as $facet_class) {
                    $facet_class = $helper->getClass($facet_class);
                    if (!class_exists($facet_class)) {
                        continue;
                    }

                    $settings[$facet][] = $facet_class;

                    $meta = $helper->resolveInjectables($facet_class);
                    if ($meta !== null
                        && $meta['alias'] !== ''
                        && !in_array($meta['alias'], $settings['aliases'][$facet], true)
                    ) {
                        $settings['aliases'][$facet][] = $meta['alias'];
                    }
                }
            }

            foreach ($config['commands'] ?? [] as $command) {
                $command = $helper->getClass($command);
                if (class_exists($command)) {
                    $settings['commands'][] = $command;
                }
            }
        }

        foreach (['providers', 'middlewares', 'commands'] as $facet) {
            $settings[$facet] = array_values(array_unique($settings[$facet]));
        }

        return $settings;
    }

    /**
     * Reads one aggregated settings list.
     *
     * @param string $key One of `providers`, `middlewares`, or `commands`.
     * @return list<string>|null Configured classes, or null for an unknown key.
     */
    public function get(string $key): ?array
    {
        if (in_array($key, ['providers', 'middlewares', 'commands'], true)) {
            return $this->settings[$key] ?? [];
        }

        return null;
    }

    /**
     * Reads the resolved facet aliases.
     *
     * @return array<string, list<string>> Aliases keyed by facet family.
     */
    public function getAliases(): array
    {
        return $this->settings['aliases'] ?? ['middlewares' => [], 'providers' => []];
    }

    /**
     * Reads the injectable closures declared by the application.
     *
     * @return InjectableConfiguration|null Injectable configuration, or null when absent.
     */
    public function getInjectables(): ?InjectableConfiguration
    {
        if (!class_exists(\App\Injectable::class)) {
            return null;
        }

        return new InjectableConfiguration(new \ReflectionClass(\App\Injectable::class));
    }
}
