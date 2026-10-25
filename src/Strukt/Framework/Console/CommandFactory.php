<?php

declare(strict_types=1);

namespace Strukt\Framework\Console;

use Strukt\Console\Runtime\CommandInterface;

/**
 * Resolves a command class into a runnable command instance.
 *
 * The shell composes command instantiation through this port so commands can
 * receive their dependencies from the container instead of being newed up
 * directly.
 */
interface CommandFactory
{
    /**
     * Builds one command instance.
     *
     * @param class-string $class Command class.
     * @return CommandInterface Command instance.
     */
    public function make(string $class): CommandInterface;
}