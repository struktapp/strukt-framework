<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

/**
 * Resolves the framework bundles configured for an application.
 *
 * {@see \Strukt\Framework\Configuration} discovers bundle settings through
 * this port instead of calling the static registry, so discovery is testable
 * and swappable.
 */
interface BundlesRepositoryInterface
{
    /**
     * Lists every configured bundle and its class.
     *
     * @return array<string, string> Bundle classes keyed by name.
     */
    public function available(): array;

    /**
     * Lists the bundle names of a given status.
     *
     * @param string $type Either `installed` or `published`.
     * @return list<string> Bundle names matching the status.
     */
    public function list(string $type): array;
}
