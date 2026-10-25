<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Framework\Console\Command\MiddlewareMake;
use Strukt\Framework\Templates;
use Strukt\Fs\Fs;

beforeEach(function () {
    $root = sys_get_temp_dir() . '/strukt-tpl-' . bin2hex(random_bytes(4));

    Env::set('root_dir', $root);
    Env::set('rel_app_lib', 'lib/App');
    Env::set('rel_appsrc', 'app/src');

    Fs::mkdir($root);
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('copies the framework templates the project is missing', function () {
    $root = (string) Env::get('root_dir');

    $created = Templates::ensure($root);

    expect($created)->toContain('lib/App/Middleware/{{class_name}}.sgf');
    expect($created)->toContain('lib/App/Loader.sgf');
    expect($created)->toContain('cfg/app.sgf');
    expect(is_file($root . '/templates/lib/App/Middleware/{{class_name}}.sgf'))->toBeTrue();
    expect((string) file_get_contents($root . '/templates/lib/App/Middleware/{{class_name}}.sgf'))
        ->toBe((string) file_get_contents(Templates::defaults() . '/lib/App/Middleware/{{class_name}}.sgf'));

    expect(Templates::ensure($root))->toBe([]);
});

it('never overwrites an existing project template', function () {
    $root = (string) Env::get('root_dir');

    Templates::ensure($root);
    Fs::overwrite($root . '/templates/lib/App/Middleware/{{class_name}}.sgf', '<?php // custom');

    Templates::ensure($root);

    expect((string) file_get_contents($root . '/templates/lib/App/Middleware/{{class_name}}.sgf'))
        ->toBe('<?php // custom');
});

it('resolves a project template before the framework default', function () {
    $root = (string) Env::get('root_dir');

    expect(Templates::path('lib/App/Middleware/{{class_name}}.sgf'))
        ->toBe(Templates::defaults() . '/lib/App/Middleware/{{class_name}}.sgf');

    Fs::touchWrite($root . '/templates/lib/App/Middleware/{{class_name}}.sgf', '<?php // custom');

    expect(Templates::path('lib/App/Middleware/{{class_name}}.sgf'))
        ->toBe($root . '/templates/lib/App/Middleware/{{class_name}}.sgf');
    expect(Templates::path('missing.sgf'))->toBeNull();
});

it('maps a template path onto its install destination', function () {
    $root = (string) Env::get('root_dir');

    expect(Templates::install('lib/App/Middleware/{{class_name}}.php', ['class_name' => 'Audit'], $root))
        ->toBe($root . '/lib/App/Middleware/Audit.php');
    expect(Templates::install('app/src/{{app}}/User.php', ['app' => 'Web'], $root))
        ->toBe($root . '/app/src/Web/User.php');
    expect(Templates::install('cfg/app.ini', [], $root))->toBe($root . '/cfg/app.ini');
    expect(Templates::install('index.php', [], $root))->toBe($root . '/index.php');
});

it('generates scaffolding from the project template', function () {
    $root = (string) Env::get('root_dir');

    Fs::touchWrite(
        $root . '/templates/lib/App/Middleware/{{class_name}}.sgf',
        "<?php\n// project template\nnamespace App\\Middleware;\nfinal class {{class_name}} {}\n",
    );

    ob_start();
    (new MiddlewareMake())->execute(['name' => 'audit'], []);
    ob_end_clean();

    expect((string) file_get_contents($root . '/lib/App/Middleware/Audit.php'))
        ->toContain('// project template');
});
