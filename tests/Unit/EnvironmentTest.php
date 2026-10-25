<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Framework\Environment;
use Strukt\Fs\Fs;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-env-' . bin2hex(random_bytes(4)));
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('creates a .env with the framework defaults', function () {
    $root = (string) Env::get('root_dir');
    Fs::mkdir($root);

    expect(Environment::ensure($root))->toBeTrue();
    expect(is_file($root . '/.env'))->toBeTrue();

    $content = (string) file_get_contents($root . '/.env');
    expect($content)->toContain('serve_host = 127.0.0.1');
    expect($content)->toContain('serve_port = 8080');
    expect($content)->toContain('serve_ctx = .');
    expect($content)->toContain('is_dev = true');
    expect($content)->toContain('rel_appsrc = app/src');
    expect($content)->toContain('rel_app_lib = lib/App');
    expect($content)->toContain('rel_mod_ini = cfg/module.ini');
});

it('never overwrites an existing .env', function () {
    $root = (string) Env::get('root_dir');
    Fs::mkdir($root);
    Fs::touchWrite($root . '/.env', "serve_port = 9000\n");

    expect(Environment::ensure($root))->toBeFalse();
    expect((string) file_get_contents($root . '/.env'))->toBe("serve_port = 9000\n");
});