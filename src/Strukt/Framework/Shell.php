<?php

declare(strict_types=1);

namespace Strukt\Framework;

use RuntimeException;
use Strukt\Console\Runtime\Input;
use Strukt\Console\Runtime\Renderer;
use Strukt\Framework\Console\CommandFactory;
use Strukt\Framework\Console\CommandRepository;
use Strukt\Framework\Console\ContainerCommandFactory;
use Strukt\Framework\Console\Documenter;
use Throwable;

/**
 * The framework console dispatcher.
 *
 * The shell owns routing raw arguments to a command: it delegates discovery and
 * parsing to the {@see CommandRepository} and documentation rendering to the
 * {@see Documenter}, leaving only dispatch in this class:
 *
 * ```php
 * exit((new Shell())->run($argv));
 * ```
 */
final class Shell
{
    /**
     * @param bool $base_commands Whether to load the framework commands.
     * @param list<string> $directories Additional command directories to scan.
     * @param string|null $docsDirectory Directory receiving generated command docs.
     * @param CommandFactory|null $factory Command resolution, or the container-backed factory.
     */
    public function __construct(
        private bool $base_commands = true,
        private array $directories = [],
        private ?string $docsDirectory = null,
        private ?CommandFactory $factory = null,
    ) {
        $this->factory ??= new ContainerCommandFactory();
    }

    /**
     * Resolves and dispatches one console command.
     *
     * @param list<string> $argv Raw console arguments.
     * @return int Exit code.
     */
    public function run(array $argv): int
    {
        try {
            ConfigLoader::load(ConfigLoader::root());

            $repository = new CommandRepository($this->base_commands, $this->directories);
            $definitions = $repository->all();

            $command = $argv[1] ?? null;
            if ($command === null || in_array($command, ['list', 'commands'], true)) {
                echo (new Renderer())->sections($repository->sections());

                return 0;
            }

            if (in_array($command, ['help', '--help', '-h'], true)) {
                $target = $argv[2] ?? null;
                if ($target === null || $target === '') {
                    echo (new Renderer())->sections($repository->sections());

                    return 0;
                }

                if (!isset($definitions[$target])) {
                    throw new RuntimeException(sprintf("Unknown command '%s'. Use 'list' to see available commands.", $target));
                }

                echo (new Renderer())->help($definitions[$target]);

                return 0;
            }

            if ($command === 'docs') {
                return (new Documenter($this->docsDirectory))->generate($definitions);
            }

            if (!isset($definitions[$command])) {
                throw new RuntimeException(sprintf("Unknown command '%s'. Use 'list' to see available commands.", $command));
            }

            if (in_array('--help', array_slice($argv, 2), true) || in_array('-h', array_slice($argv, 2), true)) {
                echo (new Renderer())->help($definitions[$command]);

                return 0;
            }

            [$arguments, $options] = (new Input())->parse($definitions[$command], $argv);
            $instance = $this->factory->make($definitions[$command]->class);

            return $instance->execute($arguments, $options);
        } catch (Throwable $error) {
            fwrite(STDERR, sprintf("Error: %s\n", $error->getMessage()));

            return 1;
        }
    }
}