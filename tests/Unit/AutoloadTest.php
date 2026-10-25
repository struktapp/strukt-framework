<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Framework\Autoload;
use Strukt\Fs\Fs;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-autoload-' . bin2hex(random_bytes(4)));
    Env::set('rel_appsrc', 'app/src');
    Env::set('rel_app_lib', 'lib/App');
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();
    Autoload::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('resolves generated lib and application classes without composer autoload', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/lib/App');
    Fs::mkdir($root . '/app/src/Payroll/Router');
    Fs::touchWrite($root . '/lib/App/Lib.php', "<?php\nnamespace App;\nfinal class Lib {}\n");
    Fs::touchWrite($root . '/app/src/Payroll/User.php', "<?php\nnamespace Payroll;\nfinal class User {}\n");
    Fs::touchWrite(
        $root . '/app/src/Payroll/Router/Index.php',
        "<?php\nnamespace Payroll\\Router;\nfinal class Index {}\n"
    );

    Autoload::register($root);

    expect(class_exists(\App\Lib::class))->toBeTrue();
    expect(class_exists(\Payroll\User::class))->toBeTrue();
    expect(class_exists(\Payroll\Router\Index::class))->toBeTrue();
});

it('does not register twice', function () {
    $root = (string) Env::get('root_dir');
    Fs::mkdir($root . '/lib/App');

    Autoload::register($root);

    $loaders = spl_autoload_functions();

    Autoload::register($root);

    expect(spl_autoload_functions())->toHaveCount(count($loaders));
});