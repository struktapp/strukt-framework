<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Framework\Templates;
use Strukt\Fs\Fs;
use Strukt\Templator;

/**
 * @command bundle:make
 * @description Create a bundle scaffold
 * @usage bundle:make <name>
 * @argument name
 * @description Bundle name
 * @required
 */
final class BundleMake implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $root = (string) env('root_dir');
        $raw = (string) $arguments['name'];
        $name = str($raw)->toSnake()->toCamel()->yield();
        $lowerName = str($raw)->toSnake()->yield();
        $pkgFile = sprintf('%s/src/Strukt/Bundle/Bundle%s.php', $root, $name);

        $created = [];
        if (!Fs::isFile($pkgFile)) {
            $created[] = 'bundle';
            Fs::mkdir($root . '/bundle');
            Fs::mkdir($root . '/src/Strukt/Bundle');

            $template = Templates::path('src/Strukt/Bundle/Bundle{{class_name}}.sgf');
            if ($template === null) {
                throw new \Strukt\Raise('Bundle template [src/Strukt/Bundle/Bundle{{class_name}}.sgf] was not found.');
            }

            $content = Templator::create((string) Fs::cat($template), [
                'name' => $name,
                'lower_name' => $lowerName,
                'class_name' => $name,
            ]);

            Fs::touchWrite($pkgFile, $content);
        }

        $pkgJson = sprintf('%s/bundle.json', $root);
        if (!Fs::isFile($pkgJson)) {
            $created[] = 'bundle.json';
            Fs::touchWrite($pkgJson, self::jsonPretty(['files' => []]));
        }

        if ($created !== []) {
            printf("Bundle scaffold[%s] created successfully.\n", implode('|', $created));
        } else {
            echo "Already exists!\n";
        }

        return 0;
    }

    private static function jsonPretty(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}