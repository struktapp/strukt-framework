<?php

declare(strict_types=1);

namespace Strukt\Framework\Console;

use Strukt\Console\Definition\CommandDefinition;
use Strukt\Console\Runtime\Renderer;

/**
 * Renders command definitions as documentation.
 *
 * Personnel separates the markdown generation from the console dispatcher, so
 * the {@see Shell} only resolves and executes commands while the rendering is
 * owned here.
 */
final class Documenter
{
    public function __construct(private ?string $directory = null)
    {
    }

    /**
     * Writes the command documentation as markdown files.
     *
     * @param array<string, CommandDefinition> $definitions Parsed definitions.
     * @return int Exit code.
     */
    public function generate(array $definitions): int
    {
        $directory = $this->directory ?? getcwd() . '/docs/commands';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $renderer = new Renderer();
        foreach ($definitions as $definition) {
            $name = str_replace(['/', ' '], '-', $definition->name);
            file_put_contents(sprintf('%s/%s.md', $directory, $name), $renderer->markdown($definition));
        }

        echo sprintf("Generated %d command documentation in %s\n", count($definitions), $directory);

        return 0;
    }
}