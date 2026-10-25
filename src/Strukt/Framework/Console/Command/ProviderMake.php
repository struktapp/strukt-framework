<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Env;
use Strukt\Framework\Templates;
use Strukt\Fs\Fs;

/**
 * @command provider:make
 * @description Create provider scaffolding
 * @usage provider:make <name>
 * @argument name
 * @description Provider name
 * @required
 */
final class ProviderMake implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $name = (string) $arguments['name'];
        $className = self::camel($name);

        $relative = 'lib/App/Provider/{{class_name}}.sgf';
        $template = Templates::path($relative);
        if ($template === null) {
            throw new \Strukt\Raise(sprintf('Provider template [%s] was not found.', $relative));
        }

        $output = strtr((string) Fs::cat($template), ['{{name}}' => $name, '{{class_name}}' => $className]);

        $path = Templates::install('lib/App/Provider/{{class_name}}.php', ['class_name' => $className]);
        Fs::mkdir(dirname($path));
        Fs::touchWrite($path, $output);

        echo "Provider created.\n";

        return 0;
    }

    /**
     * Converts an underscored name to Pascal case.
     */
    private static function camel(string $name): string
    {
        return implode('', array_map('ucfirst', preg_split('/[_-]+/', $name))) ?: $name;
    }
}