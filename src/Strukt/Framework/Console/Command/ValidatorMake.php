<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Env;
use Strukt\Framework\Templates;
use Strukt\Fs\Fs;

/**
 * @command validator:make
 * @description Create validator scaffolding
 * @usage validator:make <name>
 * @argument name
 * @description Validator name
 * @required
 */
final class ValidatorMake implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $name = (string) $arguments['name'];
        $className = self::camel($name);

        $relative = 'lib/App/Validator/{{class_name}}.sgf';
        $template = Templates::path($relative);
        if ($template === null) {
            throw new \Strukt\Raise(sprintf('Validator template [%s] was not found.', $relative));
        }

        $output = strtr((string) Fs::cat($template), ['{{name}}' => $name, '{{class_name}}' => $className]);

        $path = Templates::install('lib/App/Validator/{{class_name}}.php', ['class_name' => $className]);
        Fs::mkdir(dirname($path));
        Fs::touchWrite($path, $output);

        echo "Validator created.\n";

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
