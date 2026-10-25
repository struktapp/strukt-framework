<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Console\Command\AppMake;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-app-make-' . bin2hex(random_bytes(4)));
    Env::set('rel_appsrc', 'app/src');
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('generates a full application scaffold', function () {
    $root = (string) Env::get('root_dir');

    ob_start();
    $code = (new AppMake())->execute(['app_name' => 'payroll'], []);
    ob_end_clean();

    $module = sprintf('%s/app/src/Payroll/AuthModule', $root);

    expect($code)->toBe(0);
    expect(is_dir($module))->toBeTrue();
    expect(is_dir($module . '/Controller'))->toBeTrue();
    expect(is_dir($module . '/Router'))->toBeTrue();
    expect(is_dir($module . '/Form'))->toBeTrue();
    expect(is_dir($module . '/Tests'))->toBeTrue();
    expect(is_file($module . '/PayrollAuthModule.php'))->toBeTrue();
    expect(is_file($module . '/Controller/User.php'))->toBeTrue();
    expect(is_file($module . '/Form/User.php'))->toBeTrue();
    expect(is_file($module . '/Router/Index.php'))->toBeTrue();
    expect(is_file($module . '/Tests/UserTest.php'))->toBeTrue();
    expect(is_file($root . '/app/src/Payroll/User.php'))->toBeTrue();
    expect(is_file($root . '/cfg/app.ini'))->toBeTrue();
    expect(is_file($root . '/index.php'))->toBeTrue();
});

it('renders the front controller into the project root', function () {
    $root = (string) Env::get('root_dir');

    (new AppMake())->execute(['app_name' => 'payroll'], []);

    $index = (string) file_get_contents($root . '/index.php');

    expect($index)->toContain("require __DIR__ . '/vendor/autoload.php';");
    expect($index)->toContain('Autoload::register(__DIR__)');
    expect($index)->toContain('class_exists(Loader::class)');
    expect($index)->toContain('$app->run();');
});

it('renders the app name into the generated sources', function () {
    $root = (string) Env::get('root_dir');

    (new AppMake())->execute(['app_name' => 'payroll'], []);

    $module = (string) file_get_contents($root . '/app/src/Payroll/AuthModule/PayrollAuthModule.php');
    $router = (string) file_get_contents($root . '/app/src/Payroll/AuthModule/Router/Index.php');
    $ini = (string) file_get_contents($root . '/cfg/app.ini');

    expect($module)->toContain('namespace Payroll\\AuthModule;');
    expect($module)->toContain('class PayrollAuthModule');
    expect($module)->toContain("'au'");
    expect($router)->toContain('namespace Payroll\\AuthModule\\Router;');
    expect($router)->toContain('@Route(/)');
    expect($ini)->toContain('app-name = Payroll');
});

it('rejects an application that already exists', function () {
    $root = (string) Env::get('root_dir');
    $appDir = sprintf('%s/app/src/Payroll', $root);

    Fs::mkdir($appDir);

    (new AppMake())->execute(['app_name' => 'payroll'], []);
})->throws(\Strukt\Raise::class, 'Application [Payroll] already exists!');

it('converts underscored app names to camel case', function () {
    $root = (string) Env::get('root_dir');

    (new AppMake())->execute(['app_name' => 'pay_roll'], []);

    expect(is_dir($root . '/app/src/PayRoll/AuthModule'))->toBeTrue();

    $module = (string) file_get_contents($root . '/app/src/PayRoll/AuthModule/PayRollAuthModule.php');
    expect($module)->toContain('class PayRollAuthModule');
});

it('converts spaced and hyphenated app names to camel case', function () {
    $root = (string) Env::get('root_dir');

    (new AppMake())->execute(['app_name' => 'pay roll-two'], []);

    expect(is_dir($root . '/app/src/PayRollTwo/AuthModule'))->toBeTrue();
});