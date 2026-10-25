<?php

declare(strict_types=1);

namespace Strukt\Framework\Console;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Container\Container;
use UnexpectedValueException;

/**
 * Command factory that autowires commands through the container.
 *
 * Commands may declare constructor dependencies; every dependency is resolved
 * from the container by type.
 */
final class ContainerCommandFactory implements CommandFactory
{
    /** @var Container Container resolving commands. */
    private Container $container;

    /**
     * @param Container|null $container Container, or the shared instance.
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? container();
    }

    /**
     * @param class-string $class Command class.
     * @return CommandInterface Command instance.
     *
     * @throws UnexpectedValueException When the class is not a command.
     */
    public function make(string $class): CommandInterface
    {
        $instance = $this->container->make($class);
        if (!$instance instanceof CommandInterface) {
            throw new UnexpectedValueException('Command must implement CommandInterface.');
        }

        return $instance;
    }
}