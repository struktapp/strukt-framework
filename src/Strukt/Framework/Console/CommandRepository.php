<?php

declare(strict_types=1);

namespace Strukt\Framework\Console;

use Strukt\Console\Definition\CommandDefinition;
use Strukt\Console\Discovery\CommandDiscovery;
use Strukt\Console\Parser\DocBlockParser;
use Strukt\Framework\Configuration;
use Throwable;

/**
 * Discovers and parses the console command definitions.
 *
 * Commands are collected from the framework, from additional command
 * directories, and from the bundles configured for the application, then
 * parsed into {@see CommandDefinition} instances keyed by command name.
 */
final class CommandRepository
{
    /**
     * @param bool $base_commands Whether to load the framework commands.
     * @param list<string> $directories Additional command directories to scan.
     */
    public function __construct(
        private bool $base_commands = true,
        private array $directories = [],
    ) {
    }

    /**
     * Returns every parsed command definition.
     *
     * @return array<string, CommandDefinition> Definitions keyed by command name.
     */
    public function all(): array
    {
        $parser = new DocBlockParser();
        $definitions = [];

        foreach ($this->classes() as $class) {
            if (is_subclass_of($class, ConditionalCommand::class) && !$class::isAvailable()) {
                continue;
            }

            try {
                $definition = $parser->parse($class);
                $definitions[$definition->name] = $definition;
            } catch (Throwable) {
                continue;
            }
        }

        return $definitions;
    }

    /**
     * Returns the discovered commands grouped into the configured sections.
     *
     * Sections are read from the `cmd` configuration (cfg/cmd.ini). Each
     * section maps its `cmd` list onto the discovered definitions, preserving
     * the configuration order, and carries an optional `title` heading. A
     * section is skipped when it resolves to no commands. Commands that are
     * discovered but not configured anywhere are appended under a final
     * unlabelled group so the list always stays complete.
     *
     * @return list<array{title: string|null, commands: list<CommandDefinition>}>
     */
    public function sections(): array
    {
        $definitions = $this->all();
        $configured = [];
        $sections = [];

        foreach ((array) config('cmd') ?: [] as $entries) {
            if (!is_array($entries)) {
                continue;
            }

            $commands = [];
            foreach ((array) ($entries['cmd'] ?? []) as $name) {
                $name = (string) $name;
                if (!array_key_exists($name, $definitions)) {
                    continue;
                }

                $commands[] = $definitions[$name];
                $configured[$name] = true;
            }

            if ($commands === []) {
                continue;
            }

            $sections[] = [
                'title' => is_string($entries['title'] ?? null) ? $entries['title'] : null,
                'commands' => $commands,
            ];
        }

        $remaining = array_values(array_filter(
            $definitions,
            static fn (CommandDefinition $definition): bool => !isset($configured[$definition->name]),
        ));
        if ($remaining !== []) {
            usort($remaining, static fn (CommandDefinition $a, CommandDefinition $b): int => strcmp($a->name, $b->name));
            $sections[] = ['title' => null, 'commands' => $remaining];
        }

        return $sections;
    }

    /**
     * Resolves one parsed command definition.
     *
     * @param string $name Command name, such as `cache:clear`.
     * @return CommandDefinition|null Definition, or null when unknown.
     */
    public function find(string $name): ?CommandDefinition
    {
        return $this->all()[$name] ?? null;
    }

    /**
     * Collects the command classes from every source.
     *
     * @return list<class-string> Command classes.
     */
    private function classes(): array
    {
        $classes = [];

        if ($this->base_commands) {
            $classes = array_merge($classes, (new CommandDiscovery())->discover($this->baseDirectory()));
        }

        foreach ($this->directories as $directory) {
            if (is_dir($directory)) {
                $classes = array_merge($classes, (new CommandDiscovery())->discover($directory));
            }
        }

        foreach ($this->configured() as $class) {
            if (!in_array($class, $classes, true)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * Lists the command classes contributed by the configured bundles.
     *
     * @return list<class-string> Command classes.
     */
    private function configured(): array
    {
        if (config('app.type') === null) {
            return [];
        }

        try {
            return (new Configuration())->get('commands') ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Returns the framework command directory.
     *
     * @return string Command directory path.
     */
    private function baseDirectory(): string
    {
        return __DIR__ . '/Command';
    }
}