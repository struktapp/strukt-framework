<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

/**
 * Base reflector for an application module.
 *
 * A module groups the application facets (controllers, routers, forms, and
 * tests) that live beneath one namespace. Extending this class makes those
 * facets discoverable by the {@see \Strukt\Framework\Application}.
 */
abstract class Module
{
    /** Module alias, such as `au` for an Auth module. */
    protected string $alias = '';

    /** Reflection of the concrete module. */
    protected \ReflectionObject $reflect;

    public function __construct()
    {
        $this->reflect = new \ReflectionObject($this);
    }

    /**
     * Returns the directory that contains the module.
     *
     * @return string Absolute directory path.
     */
    public function getBaseDir(): string
    {
        return dirname($this->reflect->getFileName());
    }

    /**
     * Returns the fully qualified module class name.
     *
     * @return string Module namespace.
     */
    public function getNamespace(): string
    {
        return $this->reflect->getName();
    }

    /**
     * Returns the module alias.
     *
     * @return string Module alias.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}
