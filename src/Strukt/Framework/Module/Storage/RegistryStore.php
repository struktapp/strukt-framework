<?php

declare(strict_types=1);

namespace Strukt\Framework\Module\Storage;

use Strukt\Framework\Contract\ModuleStore;

/**
 * Module store backed by the shared `reg()` registry.
 */
final class RegistryStore implements ModuleStore
{
    /**
     * @return bool True when the key is present in the registry.
     */
    public function exists(string $key): bool
    {
        return reg()->exists($key);
    }

    /**
     * @return mixed Stored value, or null when absent.
     */
    public function get(string $key): mixed
    {
        return reg($key);
    }

    /**
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        reg($key, $value);
    }
}