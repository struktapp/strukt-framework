<?php

declare(strict_types=1);

use Strukt\Framework\Shell;

it('lists the discovered commands', function () {
    $shell = new Shell();

    ob_start();
    $code = $shell->run([str_repeat('a', 0), 'list']);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('app:reload');
    expect($output)->toContain('module:make');
    expect($output)->toContain('cache:clear');
});

it('renders help for a command', function () {
    $shell = new Shell();

    ob_start();
    $code = $shell->run(['x', 'help', 'module:make']);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Generate an application module');
});

it('groups the command list into configured sections', function () {
    $shell = new Shell();

    ob_start();
    $code = $shell->run(['x', 'list']);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Bundle Publisher');
    expect(strpos($output, 'app:make'))->toBeLessThan(strpos($output, 'Bundle Publisher'));
    expect(strpos($output, 'Bundle Publisher'))->toBeLessThan(strpos($output, 'bundle:info'));
});

it('rejects unknown commands', function () {
    $shell = new Shell();

    ob_start();
    $code = $shell->run(['x', 'bogus']);
    ob_end_clean();

    expect($code)->toBe(1);
});