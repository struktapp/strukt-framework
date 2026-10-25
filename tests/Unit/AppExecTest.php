<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Framework\Console\Command\AppExec;
use Strukt\Fs\Fs;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-exec-' . bin2hex(random_bytes(4)));
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('passes the front controller as the router script when present', function () {
    $root = (string) Env::get('root_dir');
    Fs::touchWrite($root . '/index.php', '<?php');

    $line = AppExec::command('127.0.0.1', '8080', $root);

    expect($line)->toStartWith('php -S 127.0.0.1:8080');
    expect($line)->toContain(sprintf('-t %s', $root));
    expect($line)->toEndWith(sprintf('%s/index.php', $root));
});

it('serves the docroot without a router script when index.php is missing', function () {
    $root = (string) Env::get('root_dir');

    $line = AppExec::command('127.0.0.1', '8080', $root);

    expect($line)->toBe(sprintf('php -S 127.0.0.1:8080 -t %s', $root));
});