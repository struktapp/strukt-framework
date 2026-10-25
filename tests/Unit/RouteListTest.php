<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Console\Command\AppMake;
use Strukt\Framework\Console\Command\AppReload;
use Strukt\Framework\Console\Command\RouteList;
use Strukt\Router\Router;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-routes-' . bin2hex(random_bytes(4)));
    Env::set('rel_appsrc', 'app/src');
    Env::set('rel_app_lib', 'lib/App');
    Env::set('rel_mod_ini', 'cfg/module.ini');

    reg()->set('router', new Router());
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('lists the routes of a generated application', function () {
    (new AppMake())->execute(['app_name' => 'payroll'], []);
    (new AppReload())->execute([], []);

    ob_start();
    $code = (new RouteList())->execute([], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('GET');
    expect($output)->toContain('/hello/{name:alpha}');
});

it('prints a notice when no routes are registered', function () {
    ob_start();
    $code = (new RouteList())->execute([], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('No routes registered.');
});