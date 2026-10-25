<?php

declare(strict_types=1);

use Strukt\Framework\Module\Registrar;
use Strukt\Framework\Module\Storage\MemoryStore;
use Tests\ShopModule\Router\Index as ShopIndex;
use Tests\ShopModule\ShopModule;

it('indexes facets into an injected in-memory store', function () {
    $store = new MemoryStore();
    $registrar = new Registrar($store, [
        'ctr' => 'Controller',
        'rtr' => 'Router',
        'frm' => 'Form',
        'tes' => 'Tests',
    ]);

    $registrar->register(new ShopModule());

    expect($store->exists('nr.routes.shop'))->toBeTrue();
    expect($store->get('nr.routes.shop'))->toContain(ShopIndex::class);
    expect($registrar->modules())->toHaveKey('shop');
    expect($registrar->aliases())->toContain('shop');
});

it('keeps injected modules isolated from the shared registry', function () {
    reg()->remove('nr.routes.shop');

    $store = new MemoryStore();
    $registrar = new Registrar($store, ['rtr' => 'Router']);

    $registrar->register(new ShopModule());

    expect(reg('nr.routes.shop'))->toBeNull();
});

it('resolves the registrar from the shared container', function () {
    $registrar = container()->get(Registrar::class);

    expect($registrar)->toBeInstanceOf(Registrar::class);
    expect(container()->get(Registrar::class))->not->toBe($registrar);
});