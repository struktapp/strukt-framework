<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Env;
use Strukt\Framework\Environment;
use Strukt\Framework\Reloader;
use Strukt\Framework\Templates;
use Strukt\Fs\Fs;

/**
 * @command app:reload
 * @description Generate the application module loader
 */
final class AppReload implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $root = (string) Env::get('root_dir');
        $lib = (string) Env::get('rel_app_lib');

        if (Environment::ensure($root)) {
            echo ".env generated.\n";
        }

        $templates = Templates::ensure($root);
        if ($templates !== []) {
            printf("Templates generated: %d file(s).\n", count($templates));
        }

        $configs = Reloader::ensureConfigs($root);
        if ($configs !== []) {
            printf("Configs generated: %s\n", implode(', ', $configs));
        }

        if (Reloader::ensureFrontController($root)) {
            echo "Front controller generated.\n";
        }

        Fs::mkdir(sprintf('%s/%s', rtrim($root, '/'), $lib));

        $loader = sprintf('%s/%s/Loader.php', rtrim($root, '/'), $lib);
        if (Fs::isFile($loader)) {
            Fs::rm($loader);
        }

        $created = Fs::touchWrite($loader, (string) new Reloader());
        echo $created
            ? "Application loader generated successfully.\n"
            : "***Error occurred: loader generation failed!\n";

        if ($created && Reloader::ensureFacetProvider($root)) {
            echo "Facet provider generated.\n";
        }

        return $created ? 0 : 1;
    }
}