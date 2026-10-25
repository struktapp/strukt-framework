<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;

/**
 * @command cache:reset
 * @description Reset the configuration cache (clear)
 */
final class CacheReset implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        passthru('php bin/strukt cache:clear', $clear);
        passthru('php bin/strukt cache:make', $make);

        return ($clear === 0 && $make === 0) ? 0 : 1;
    }
}