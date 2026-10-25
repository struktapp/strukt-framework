<?php

declare(strict_types=1);

namespace Strukt\Framework\Traits;

use Strukt\Framework\Injectable\Facet as InjectableFacet;

/**
 * Resolves the injectable metadata of a facet class.
 */
trait InjectablesHelper
{
    /**
     * Returns the class, alias, and requirement flag of a facet.
     *
     * @param string $class Facet class name.
     * @return array{class: string, alias: string, required: bool}|null Metadata, or null when unnamed.
     */
    public static function resolveInjectables(string $class): ?array
    {
        $configs = (new InjectableFacet(new \ReflectionClass($class)))->getConfigs();
        if ($configs === null) {
            return null;
        }

        return [
            'class' => (string) $configs['class'],
            'alias' => (string) ($configs['config']['name'] ?? ''),
            'required' => (bool) ($configs['config']['is_required'] ?? false),
        ];
    }
}
