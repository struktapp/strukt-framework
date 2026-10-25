<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Framework\ConfigLoader;
use Strukt\Fs\Fs;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-config-' . bin2hex(random_bytes(4)));
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('merges a project cfg file over any existing section values', function () {
    $root = (string) Env::get('root_dir');
    $section = 'zz' . bin2hex(random_bytes(4));

    Fs::mkdir($root . '/cfg');
    Fs::touchWrite($root . sprintf('/cfg/%s.ini', $section), "added = 1\n");
    config($section, ['keep' => 'kept']);

    $loaded = ConfigLoader::load($root);

    expect($loaded)->toBe([$section]);
    expect(config(sprintf('%s.keep', $section)))->toBe('kept');
    expect(config(sprintf('%s.added', $section)))->toBe(1);
});

it('loads every ini file in the project cfg directory', function () {
    $root = (string) Env::get('root_dir');

    $a = 'zzloada';
    $b = 'zzloadb';

    Fs::mkdir($root . '/cfg');
    Fs::touchWrite($root . sprintf('/cfg/%s.ini', $a), "a = 1\n");
    Fs::touchWrite($root . sprintf('/cfg/%s.ini', $b), "b = 2\n");
    Fs::touchWrite($root . '/cfg/notes.txt', 'ignored');

    $loaded = ConfigLoader::load($root);

    expect($loaded)->toBe([$a, $b]);
    expect(config(sprintf('%s.a', $a)))->toBe(1);
    expect(config(sprintf('%s.b', $b)))->toBe(2);
});

it('is a no-op when the project has no cfg directory', function () {
    expect(ConfigLoader::load((string) Env::get('root_dir')))->toBe([]);
});

it('resolves the project root from the environment', function () {
    $root = (string) Env::get('root_dir');

    expect(ConfigLoader::root())->toBe($root);
});
