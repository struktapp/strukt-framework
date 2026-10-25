<?php

declare(strict_types=1);

namespace Strukt\Framework\Injectable;

use Strukt\Annotation\Parser;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\ProviderInterface;
use Strukt\Framework\Contract\Injectable;

/**
 * Reads the facet configuration declared on a middleware or provider class.
 *
 * A facet is named with `@Name`, optionally marked `@Required`, and can declare
 * its registry dependencies with `@Requires` and `@Inject`:
 *
 * ```php
 * /** @Name(valid) @Required() *\/
 * final class Validator implements ProviderInterface {}
 * ```
 *
 * The generated configuration is consumed while assembling an application's
 * providers and middlewares.
 */
final class Facet implements Injectable
{
    /** @var array<string, mixed>|null Parsed facet configuration. */
    private ?array $notes = null;

    public function __construct(\ReflectionClass $reflection)
    {
        $classNotes = (new Parser($reflection))->getNotes()['class'] ?? [];
        $name = isset($classNotes['Name']['item']) ? (string) $classNotes['Name']['item'] : '';

        if ($name === '') {
            return;
        }

        $kind = $this->kind($reflection);
        if ($kind === null || !$this->enabled($kind, $name)) {
            return;
        }

        $settings = ['name' => $name, 'is_required' => false];

        if (array_key_exists('Required', $classNotes)) {
            $settings['is_required'] = true;
        }

        if (array_key_exists('Requires', $classNotes)) {
            $settings['requires'][] = $classNotes['Requires']['item'];
        }

        if (array_key_exists('Inject', $classNotes)) {
            $settings['inject'] = $classNotes['Inject']['item'];
        }

        $this->notes = [
            'class' => $reflection->getName(),
            'config' => $settings,
        ];
    }

    /**
     * Returns the parsed facet configuration.
     *
     * @return array<string, mixed>|null Facet configuration, or null when unnamed.
     */
    public function getConfigs(): ?array
    {
        return $this->notes;
    }

    /**
     * Determines which facet family a class belongs to.
     *
     * @param \ReflectionClass $reflection Reflected facet.
     * @return string|null `middleware`, `provider`, or null when neither.
     */
    private function kind(\ReflectionClass $reflection): ?string
    {
        $interfaces = $reflection->getInterfaceNames();

        if (in_array(MiddlewareInterface::class, $interfaces, true)) {
            return 'middleware';
        }

        if (in_array(ProviderInterface::class, $interfaces, true)) {
            return 'provider';
        }

        return null;
    }

    /**
     * Reports whether a facet is enabled for the current application.
     *
     * When the matching `app.middlewares` or `app.providers` configuration is
     * absent the facet is treated as enabled, which keeps the framework usable
     * without an explicit application manifest.
     *
     * @param string $kind Facet family.
     * @param string $name Facet name.
     * @return bool True when the facet is enabled.
     */
    private function enabled(string $kind, string $name): bool
    {
        $configured = config('app.' . $kind . 's');
        if (!is_array($configured)) {
            return true;
        }

        return array_key_exists($name, $configured);
    }
}
