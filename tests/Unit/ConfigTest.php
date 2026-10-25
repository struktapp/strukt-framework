<?php

declare(strict_types=1);

use Strukt\Bundle\Bundles;

it('loads the framework cfg sections into the shared registry', function () {
    expect(config('cmd'))->toBeArray();
    expect(config('module'))->toBeArray();
    expect(config('cache'))->toBeArray();
    expect(config('repo'))->toBeArray();
});

it('exposes the module folders through a dot path', function () {
    expect(config('module.folders'))->toBe([
        'ctr' => 'Controller',
        'rtr' => 'Router',
        'frm' => 'Form',
        'tes' => 'Tests',
    ]);
});

it('keeps the core repository listed', function () {
    expect(Bundles::available())->toHaveKey('core');
    expect(Bundles::list('installed'))->toContain('core');
    expect(Bundles::list('published'))->toContain('core');
});

it('resolves packages through the repo() helper', function () {
    expect(repos())->toHaveKey('core');
});