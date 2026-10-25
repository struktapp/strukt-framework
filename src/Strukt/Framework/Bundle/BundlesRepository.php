<?php

declare(strict_types=1);

namespace Strukt\Framework\Bundle;

use Strukt\Bundle\Bundles;
use Strukt\Framework\Contract\BundlesRepositoryInterface;

/**
 * Bundle repository bound to the static `Strukt\Bundle\Bundles` registry.
 */
final class BundlesRepository implements BundlesRepositoryInterface
{
    /**
     * @return array<string, string> Bundle classes keyed by name.
     */
    public function available(): array
    {
        return Bundles::available();
    }

    /**
     * @return list<string> Bundle names matching the status.
     */
    public function list(string $type): array
    {
        return Bundles::list($type);
    }
}
