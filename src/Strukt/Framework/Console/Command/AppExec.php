<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Env;
use Strukt\Framework\Environment;
use Strukt\Framework\Reloader;
use Strukt\Fs\Fs;

/**
 * @command app:exec
 * @description Run the application on the PHP built-in server
 * @usage app:exec [<port>] [--log]
 * @argument port
 * @description Http port override
 * @type int
 * @option log
 * @short l
 * @flag
 * @description Enable server logs
 */
final class AppExec implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $host = Env::has('serve_host') ? (string) Env::get('serve_host') : 'localhost';
        $context = Env::has('serve_ctx') ? (string) Env::get('serve_ctx') : '.';
        $port = (string) ($arguments['port'] ?? (Env::has('serve_port') ? Env::get('serve_port') : '8080'));

        $root = rtrim((string) Env::get('root_dir'), '/');
        $context = ltrim($context, '/');
        if (str_contains($context, '/') || str_starts_with($context, '.')) {
            $context = rtrim(sprintf('%s/%s', $root, $context), '/');
        }

        if (Environment::ensure($root)) {
            echo ".env generated.\n";
        }

        if (Reloader::ensureFrontController($root)) {
            echo "Front controller generated.\n";
        }

        passthru(self::command($host, $port, $context), $code);

        return (int) $code;
    }

    /**
     * Builds the built-in server command line.
     *
     * When the docroot holds the scaffolded front controller, it is passed as
     * the router script so every request dispatches through the application.
     */
    public static function command(string $host, string $port, string $context): string
    {
        $line = sprintf('php -S %s:%s -t %s', $host, $port, $context);

        if (Fs::isFile(sprintf('%s/index.php', $context))) {
            $line .= sprintf(' %s/index.php', $context);
        }

        return $line;
    }
}