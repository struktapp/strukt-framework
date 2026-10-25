<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Framework\Console\Command\ValidatorMake;
use Strukt\Fs\Fs;

beforeEach(function () {
    $root = sys_get_temp_dir() . '/strukt-val-' . bin2hex(random_bytes(4));

    Env::set('root_dir', $root);
    Env::set('rel_app_lib', $root . '/lib/App');
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('scaffolds a validator under the app library', function () {
    $root = (string) Env::get('root_dir');

    ob_start();
    $code = (new ValidatorMake())->execute(['name' => 'phone'], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Validator created.');

    $path = $root . '/lib/App/Validator/Phone.php';
    expect(is_file($path))->toBeTrue();

    $source = (string) file_get_contents($path);
    expect($source)->toContain('namespace App\\Validator;');
    expect($source)->toContain('final class Phone implements Validator');
    expect($source)->toContain('@Name(phone)');
});
