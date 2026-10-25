<?php

declare(strict_types=1);

namespace Strukt\Framework\Console;

use Strukt\Console\Runtime\CommandInterface;
use UnexpectedValueException;

/**
 * Command factory that instantiates a command directly.
 */
final class PlainCommandFactory implements CommandFactory
{
    /**
     * @param class-string $class Command class.
     * @return CommandInterface Command instance.
     *
     * @throws UnexpectedValueException When the class is not a command.
     */
    public function make(string $class): CommandInterface
    {
        $instance = new $class();
        if (!$instance instanceof CommandInterface) {
            throw new UnexpectedValueException('Command must implement CommandInterface.');
        }

        return $instance;
    }
}