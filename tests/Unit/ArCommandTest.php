<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Console\Command\DbCreate;
use Strukt\Framework\Console\Command\ModelGenerate;
use Strukt\Framework\Console\CommandRepository;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-ar-' . bin2hex(random_bytes(4)));
    Env::set('rel_appsrc', 'app/src');
    Env::set('rel_app_lib', 'lib/App');
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('hides the ar commands when Strukt Ar is not installed', function () {
    expect(ModelGenerate::isAvailable())->toBeFalse()
        ->and(DbCreate::isAvailable())->toBeFalse();

    $commands = (new CommandRepository())->all();

    expect($commands)->not->toHaveKey('model:generate')
        ->not->toHaveKey('db:create');
});

it('lists the ar commands when Strukt Ar is present', function () {
    $root = (string) Env::get('root_dir');
    Fs::mkdir($root . '/app/src/Web');

    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    $autoloadExport = var_export($autoload, true);
    $rootExport = var_export($root, true);
    $script = <<<PHP
    <?php
    namespace {
        require {$autoloadExport};
    }
    namespace Strukt\\Ar {
        class Model {}
    }
    namespace Strukt\\Generate\\Command {
        class ModelGenerate implements \\Strukt\\Console\\Runtime\\CommandInterface {
            public function execute(array \$arguments, array \$options): int {
                echo 'delegated:' . \$options['dir'] . '|' . \$options['namespace'];
                return 0;
            }
        }
        class DbCreate implements \\Strukt\\Console\\Runtime\\CommandInterface {
            public function execute(array \$arguments, array \$options): int { return 0; }
        }
    }
    namespace {
        use Strukt\\Env;
        use Strukt\\Framework\\Shell;
        Env::set('root_dir', {$rootExport});
        Env::set('rel_appsrc', 'app/src');
        exit((new Shell())->run(['x', 'model:generate']));
    }
    PHP;

    $file = $root . '/run.php';
    Fs::touchWrite($file, $script);

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open([PHP_BINARY, $file], $descriptors, $pipes, $root, ['PATH' => (string) getenv('PATH')]);
    expect(is_resource($process))->toBeTrue();

    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);

    expect($stderr)->toBe('')
        ->and($code)->toBe(0)
        ->and($stdout)->toContain('delegated:' . $root . '/app/src/Web|Web');
});

it('gates conditional commands behind their availability', function () {
    $root = (string) Env::get('root_dir');
    $namespace = 'Probe' . bin2hex(random_bytes(4));
    $directory = $root . '/commands';
    Fs::mkdir($directory);

    Fs::touchWrite($directory . '/Ok.php', sprintf(
        "<?php\nnamespace %s;\nuse Strukt\\Console\\Runtime\\CommandInterface;\nuse Strukt\\Framework\\Console\\ConditionalCommand;\n/**\n * @command probe:ok\n * @description An available command\n */\nfinal class Ok implements CommandInterface, ConditionalCommand {\n    public static function isAvailable(): bool { return true; }\n    public function execute(array \$a, array \$o): int { return 0; }\n}\n",
        $namespace,
    ));
    Fs::touchWrite($directory . '/Missing.php', sprintf(
        "<?php\nnamespace %s;\nuse Strukt\\Console\\Runtime\\CommandInterface;\nuse Strukt\\Framework\\Console\\ConditionalCommand;\n/**\n * @command probe:missing\n * @description A missing command\n */\nfinal class Missing implements CommandInterface, ConditionalCommand {\n    public static function isAvailable(): bool { return false; }\n    public function execute(array \$a, array \$o): int { return 0; }\n}\n",
        $namespace,
    ));

    $commands = (new CommandRepository(false, [$directory]))->all();

    expect($commands)->toHaveKey('probe:ok')
        ->not->toHaveKey('probe:missing');
});

it('defaults model targets to the application source', function () {
    $root = (string) Env::get('root_dir');
    Fs::mkdir($root . '/app/src/Web');

    $namespace = new ReflectionMethod(ModelGenerate::class, 'resolveNamespace');
    $dir = new ReflectionMethod(ModelGenerate::class, 'resolveDir');

    expect($namespace->invoke(null, null))->toBe('Web')
        ->and($dir->invoke(null, null, 'Web'))->toBe($root . '/app/src/Web')
        ->and($namespace->invoke(null, 'Web\\Model'))->toBe('Web\\Model')
        ->and($dir->invoke(null, null, 'Web\\Model'))->toBe($root . '/app/src/Web/Model')
        ->and($dir->invoke(null, 'custom/models', 'Web'))->toBe('custom/models');
});

it('requires an explicit namespace when no application exists', function () {
    $namespace = new ReflectionMethod(ModelGenerate::class, 'resolveNamespace');

    $namespace->invoke(null, null);
})->throws(\Strukt\Raise::class, 'Pass --namespace');
