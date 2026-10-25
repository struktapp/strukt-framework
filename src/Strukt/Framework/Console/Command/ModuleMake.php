<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Env;
use Strukt\Fs\Fs;

/**
 * @command module:make
 * @description Generate an application module
 * @usage module:make <app_name> <module_name> <alias_name>
 * @argument app_name
 * @description Application name (underscored names become camel case)
 * @required
 * @argument module_name
 * @description Module name
 * @required
 * @argument alias_name
 * @description Module alias
 * @required
 */
final class ModuleMake implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $appName = self::camel((string) $arguments['app_name']);
        $moduleName = self::camel((string) $arguments['module_name']);
        if (!str_ends_with($moduleName, 'Module')) {
            $moduleName .= 'Module';
        }
        $alias = self::camel((string) $arguments['alias_name']);

        $root = rtrim((string) Env::get('root_dir'), '/');
        $appSrc = (string) Env::get('rel_appsrc');

        $moduleNs = sprintf('%s\\%s\\%s%s', $appName, $moduleName, $appName, $moduleName);
        $registered = (array) reg('nr.modules') ?: [];
        if (in_array($moduleNs, $registered, true)) {
            throw new \Strukt\Raise('Module already exists!');
        }

        $moduleDir = sprintf('%s/%s/%s/%s', $root, $appSrc, $appName, $moduleName);
        Fs::mkdir($moduleDir);

        foreach ((array) config('module.folders') ?: [] as $folder) {
            Fs::mkdir(sprintf('%s/%s', $moduleDir, $folder));
        }

        $class = sprintf('%s%s', $appName, $moduleName);
        $source = sprintf("<?php\n\nnamespace %s\\%s;\n\nuse Strukt\\Framework\\Contract\\Module;\n\nclass %s extends Module\n{\n    protected \$alias = \"%s\";\n}\n", $appName, $moduleName, $class, $alias);

        Fs::touchWrite(sprintf('%s/%s.php', $moduleDir, $class), $source);
        echo "Module generated successfully.\n";

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