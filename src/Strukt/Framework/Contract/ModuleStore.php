<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

/**
 * Read/write store for the module registry (`nr.*`) entries.
 *
 * The registrar never touches the global registry directly: it writes through
 * this port so the registry can be swapped for an in-memory store in tests or
 * other runtimes.
 */
interface ModuleStore
{
    /**
     * Checks whether a key is present.
     *
     * @param string $key Key, such as `nr.routes.shop`.
     * @return bool True when present.
     */
    public function exists(string $key): bool;

    /**
     * Reads one entry.
     *
     * @param string $key Key.
     * @return mixed Stored value, or null when absent.
     */
    public function get(string $key): mixed;

    /**
     * Stores or replaces one entry.
     *
     * @param string $key Key.
     * @param mixed $value Value to store.
     * @return void
     */
    public function set(string $key, mixed $value): void;
}