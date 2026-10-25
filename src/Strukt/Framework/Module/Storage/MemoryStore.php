<?php

declare(strict_types=1);

namespace Strukt\Framework\Module\Storage;

use Strukt\Framework\Contract\ModuleStore;

/**
 * In-memory module store for tests and runtimes without a global registry.
 */
final class MemoryStore implements ModuleStore
{
    /** @var array<string, mixed> Stored entries. */
    private array $entries = [];

    /**
     * @return bool True when the key is present.
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->entries);
    }

    /**
     * @return mixed Stored value, or null when absent.
     */
    public function get(string $key): mixed
    {
        return $this->entries[$key] ?? null;
    }

    /**
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->entries[$key] = $value;
    }
}