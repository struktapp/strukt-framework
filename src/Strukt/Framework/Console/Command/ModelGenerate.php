<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Framework\Console\ArCommandSupport;
use Strukt\Framework\Console\ConditionalCommand;
use Strukt\Generate\Command\ModelGenerate as ArModelGenerate;

/**
 * @command model:generate
 * @description Generate Strukt Ar models from the database schema
 * @usage model:generate [--database=<db>] [options]
 *
 * @option database
 * @type string
 * @description Database name, file, or `:memory:`. Omit to use the `.env` connection.
 *
 * @option env
 * @type string
 * @default .env
 * @description Comma-separated environment file(s) used when `--database` is omitted.
 *
 * @option driver
 * @type string
 * @default sqlite
 * @enum sqlite,mysql,pgsql
 * @description Database driver.
 *
 * @option host
 * @type string
 * @default 127.0.0.1
 * @description Database host. Used by mysql and pgsql.
 *
 * @option port
 * @type int
 * @description Database port. Defaults to 3306 for mysql and 5432 for pgsql.
 *
 * @option user
 * @type string
 * @default root
 * @description Database user. Used by mysql and pgsql.
 *
 * @option password
 * @type string
 * @default ""
 * @description Database password. Used by mysql and pgsql.
 *
 * @option dir
 * @type string
 * @description Directory to write generated models into. Defaults to the application source directory.
 *
 * @option namespace
 * @type string
 * @description Namespace for the generated models. Defaults to the application root namespace.
 *
 * @option table
 * @type string
 * @description Restrict generation to a single table.
 */
final class ModelGenerate implements CommandInterface, ConditionalCommand
{
    use ArCommandSupport;

    public function execute(array $arguments, array $options): int
    {
        $namespace = self::resolveNamespace($options['namespace'] ?? null);
        $options['namespace'] = $namespace;
        $options['dir'] = self::resolveDir($options['dir'] ?? null, $namespace);

        return (new ArModelGenerate())->execute($arguments, $options);
    }
}
