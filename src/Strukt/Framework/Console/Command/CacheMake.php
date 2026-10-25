<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Framework\Configuration;

/**
 * @command cache:make
 * @description Rebuild the application configuration cache
 * @usage cache:make
 */
final class CacheMake implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $settings = Configuration::create((string) config('app.type'));
        cache('app')->put(config('app.type'), $settings)->save();

        echo "Cache recreated.\n";

        return 0;
    }
}