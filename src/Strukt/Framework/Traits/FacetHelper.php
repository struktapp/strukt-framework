<?php

declare(strict_types=1);

namespace Strukt\Framework\Traits;

/**
 * Resolves module facet aliases into class namespaces.
 */
trait FacetHelper
{
    /**
     * Reports whether an alias uses the qualified `<module>.<facet>.<class>` form.
     *
     * @param string $alias Facet alias.
     * @return bool True when the alias is fully qualified.
     */
    public function isQualifiedAlias(string $alias): bool
    {
        return (bool) preg_match('/[a-z]{2}\.[a-z]{3}\.\w+/', $alias);
    }

    /**
     * Resolves a facet alias into a namespace.
     *
     * @param string $alias_ns Qualified alias or a relative class name.
     * @return string Resolved namespace.
     */
    public function getNamespace(string $alias_ns): string
    {
        if ($this->isQualifiedAlias($alias_ns)) {
            return (string) reg(sprintf('nr.%s', $alias_ns));
        }

        return sprintf('%s\\%s', (string) config('app.name'), $alias_ns);
    }
}
