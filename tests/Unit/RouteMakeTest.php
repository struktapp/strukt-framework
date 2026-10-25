<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Autoload;
use Strukt\Framework\Console\Command\AppMake;
use Strukt\Framework\Console\Command\AppReload;
use Strukt\Framework\Console\Command\RouteMake;

beforeEach(function () {
    $this->app = 'payroll' . bin2hex(random_bytes(3));

    Env::set('root_dir', sys_get_temp_dir() . '/strukt-route-make-' . bin2hex(random_bytes(4)));
    Env::set('rel_appsrc', 'app/src');
    Env::set('rel_app_lib', 'lib/App');
    Env::set('rel_mod_ini', 'cfg/module.ini');

    $root = (string) Env::get('root_dir');

    (new AppMake())->execute(['app_name' => $this->app], []);
    (new AppReload())->execute([], []);

    $this->routerDir = sprintf(
        '%s/app/src/%s/AuthModule/Router',
        $root,
        ucfirst($this->app),
    );
});

afterEach(function () {
    $root = (string) Env::get('root_dir');

    Autoload::reset();
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('creates a router with an annotated route method', function () {
    ob_start();
    $code = (new RouteMake())->execute(['module' => 'au', 'name' => 'User', 'route' => '/user/all'], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Router created.');

    $path = $this->routerDir . '/User.php';
    expect(is_file($path))->toBeTrue();

    $source = (string) file_get_contents($path);
    expect($source)->toContain(sprintf('namespace %s\\AuthModule\\Router;', ucfirst($this->app)));
    expect($source)->toContain('use Strukt\\Framework\\Contract\\Router;');
    expect($source)->toContain('use Strukt\\Contract\\Http\\RequestInterface;');
    expect($source)->toContain('final class User extends Router');
    expect($source)->toContain('@Method(GET)');
    expect($source)->toContain('@Route(/user/all)');
    expect($source)->toContain('public function userAll(RequestInterface $request)');
});

it('appends a second route to an existing router', function () {
    (new RouteMake())->execute(['module' => 'au', 'name' => 'User', 'route' => '/user/all'], []);

    ob_start();
    $code = (new RouteMake())->execute(['module' => 'au', 'name' => 'User', 'route' => '/user/{id}'], ['method' => 'POST']);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Route added to User.');

    $source = (string) file_get_contents($this->routerDir . '/User.php');
    expect(substr_count($source, 'public function'))->toBe(2);
    expect($source)->toContain('@Route(/user/all)');
    expect($source)->toContain('@Method(POST)');
    expect($source)->toContain('@Route(/user/{id})');
    expect($source)->toContain('public function user(RequestInterface $request, string $id)');
});

it('maps typed placeholders to php types', function () {
    (new RouteMake())->execute(['module' => 'au', 'name' => 'Ledger', 'route' => '/ledger/{id:int}/{slug:alpha}/{ratio:numeric}'], []);

    $source = (string) file_get_contents($this->routerDir . '/Ledger.php');
    expect($source)->toContain('public function ledger(RequestInterface $request, int $id, string $slug, float $ratio)');
});

it('emits a permission annotation', function () {
    (new RouteMake())->execute(['module' => 'au', 'name' => 'Admin', 'route' => '/admin/users'], ['permission' => 'user-all']);

    $source = (string) file_get_contents($this->routerDir . '/Admin.php');
    expect($source)->toContain('@Permission(user-all)');
});

it('honours an explicit function name', function () {
    (new RouteMake())->execute(['module' => 'au', 'name' => 'User', 'route' => '/user/all'], ['function' => 'list_all']);

    $source = (string) file_get_contents($this->routerDir . '/User.php');
    expect($source)->toContain('public function listAll(RequestInterface $request)');
});

it('rejects an unknown module', function () {
    (new RouteMake())->execute(['module' => 'nope', 'name' => 'User', 'route' => '/user/all'], []);
})->throws(\Strukt\Raise::class, 'Unknown module [nope].');

it('requires arguments when not interactive', function () {
    (new RouteMake())->execute(['module' => 'au', 'name' => 'User', 'route' => null], []);
})->throws(\Strukt\Raise::class, 'Argument <route> is required');
