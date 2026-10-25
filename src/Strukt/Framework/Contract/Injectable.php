<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

/**
 * Describes a class whose configuration is read from annotations.
 */
interface Injectable
{
    /**
     * Returns the configuration parsed from the class annotations.
     *
     * @return array<string, mixed>|null Parsed configuration, or null when absent.
     */
    public function getConfigs(): ?array;
}
