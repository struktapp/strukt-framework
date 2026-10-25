<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Fs\Fs;

/**
 * @command bundle:export
 * @description Export a bundle
 * @usage bundle:export <name>
 * @argument name
 * @description Bundle name
 * @required
 */
final class BundleExport implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $root = (string) env('root_dir');
        $raw = (string) $arguments['name'];
        $snake = str($raw)->toSnake()->yield();
        $name = str($raw)->toSnake()->toCamel()->yield();

        $dir = sprintf('%s/bundle-%s', $root, $snake);

        if (Fs::isDir(sprintf('%s/bundle', $root))) {
            Fs::cpr(sprintf('%s/bundle', $root), sprintf('%s/bundle', $dir));
        }

        $pkgDirCls = sprintf('%s/src/Strukt/Bundle', $dir);
        Fs::mkdir($pkgDirCls);

        $sourcePkgCls = sprintf('%s/src/Strukt/Bundle/Bundle%s.php', $root, $name);
        if (Fs::isFile($sourcePkgCls)) {
            Fs::cpr($sourcePkgCls, sprintf('%s/Bundle%s.php', $pkgDirCls, $name));
        }

        foreach (['.env', '.gitignore'] as $file) {
            if (Fs::isFile(sprintf('%s/%s', $root, $file))) {
                Fs::cpr(sprintf('%s/%s', $root, $file), sprintf('%s/%s', $dir, $file));
            }
        }

        $composer = [
            'name' => sprintf('strukt/bundle-%s', $snake),
            'description' => 'N/A',
            'type' => 'strukt-module',
            'license' => 'MIT',
            'autoload' => [
                'psr-4' => [
                    'Strukt\\Bundle\\' => 'src/Strukt/Bundle/',
                ],
            ],
            'require-dev' => [
                'strukt/framework' => 'dev-master',
            ],
            'minimum-stability' => 'dev',
        ];

        Fs::touchWrite(sprintf('%s/composer.json', $dir), json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        Fs::touchWrite(
            sprintf('%s/README.md', $dir),
            implode("\n", [sprintf('Strukt %s', $name), '===']) . "\n",
        );

        printf("Files moved to [bundle-%s]!\n", $snake);

        return 0;
    }
}