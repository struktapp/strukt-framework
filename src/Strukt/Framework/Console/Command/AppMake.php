<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Reloader;
use Strukt\Framework\Templates;
use Strukt\Raise;
use Strukt\Templator;

/**
 * @command app:make
 * @description Generate application scaffolding
 * @usage app:make [<app_name>]
 * @argument app_name
 * @description Application name (underscored names become camel case; prompted when omitted)
 */
final class AppMake implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $appName = self::resolveName($arguments['app_name'] ?? null);

        $root = rtrim((string) Env::get('root_dir'), '/');
        $appSrc = trim((string) Env::get('rel_appsrc'), '/');

        $appDir = sprintf('%s/%s/%s', $root, $appSrc, $appName);
        if (Fs::isDir($appDir)) {
            throw new Raise(sprintf('Application [%s] already exists!', $appName));
        }

        $templates = Templates::ensure($root);
        if ($templates !== []) {
            printf("Templates generated: %d file(s).\n", count($templates));
        }

        $templateRoot = Templates::path('app/src') ?? sprintf('%s/app/src', Templates::dir());
        if (!Fs::isDir($templateRoot)) {
            throw new Raise(sprintf('Application templates [%s] were not found.', $templateRoot));
        }

        $data = ['app' => $appName];

        $generated = 0;
        foreach (Fs::lsr($templateRoot) as $templateFile) {
            $relative = Templator::create(substr($templateFile, strlen($templateRoot) + 1), $data);
            $destination = Templates::install(
                sprintf('app/src/%s', (string) preg_replace('/\.sgf$/', '.php', $relative)),
                $data,
                $root,
            );

            if (!Fs::touchWrite($destination, Templator::create((string) Fs::cat($templateFile), $data))) {
                throw new Raise(sprintf('Could not write [%s].', $destination));
            }

            $generated++;
        }

        if ($generated === 0) {
            throw new Raise(sprintf('No source templates found under [%s].', $templateRoot));
        }

        if (Reloader::ensureFrontController($root)) {
            echo "Front controller generated.\n";
        }

        $configs = Reloader::ensureConfigs($root, $appName);
        if ($configs !== []) {
            printf("Configs generated: %s\n", implode(', ', $configs));
        }

        echo "Successfully generated {$appName} application!\n";

        return 0;
    }

    /**
     * Resolves the application name from the argument or an interactive prompt.
     *
     * @param mixed $name Provided name argument.
     * @return string Pascal-cased application name.
     */
    private static function resolveName(mixed $name): string
    {
        $resolved = trim((string) $name);
        if ($resolved === '') {
            $terminal = self::terminal();
            if ($terminal === null) {
                throw new Raise('Application name is required when not running interactively.');
            }

            fwrite($terminal['out'], 'Application Name: ');
            $resolved = trim((string) fgets($terminal['in']));
        }

        $resolved = self::camel($resolved);
        if ($resolved === '') {
            throw new Raise('An application name is required.');
        }

        return $resolved;
    }

    /**
     * Locates a terminal for interactive prompts.
     *
     * Prefers the standard input when it is a terminal; otherwise falls back to
     * the controlling terminal so prompts keep working when the output is
     * captured (for example inside Composer's create-project hook).
     *
     * @return array{in: resource, out: resource}|null Terminal streams, or null when unavailable.
     */
    private static function terminal(): ?array
    {
        if (defined('STDIN') && function_exists('stream_isatty') && @stream_isatty(STDIN)) {
            return ['in' => STDIN, 'out' => defined('STDOUT') ? STDOUT : STDIN];
        }

        $tty = @fopen('/dev/tty', 'r+');
        if ($tty === false) {
            return null;
        }

        return ['in' => $tty, 'out' => $tty];
    }

    /**
     * Converts an underscored, spaced, or hyphenated name to Pascal case.
     */
    private static function camel(string $name): string
    {
        return implode('', array_map('ucfirst', preg_split('/[_\s-]+/', $name))) ?: $name;
    }
}