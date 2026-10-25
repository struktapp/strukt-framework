<?php

declare(strict_types=1);

use Strukt\Framework\Application;
use Strukt\Router\Router;
use Tests\ShopModule\Router\Index as ShopIndex;
use Tests\ShopModule\ShopModule;

beforeEach(function () {
    $this->router = new Router();
    $this->application = Application::make($this->router);
});

it('registers a module and indexes its router facet by alias', function () {
    $this->application->register(new ShopModule());

    expect($this->application->modules())->toHaveKey('shop');
    expect(reg('nr.routes.shop'))->toContain(ShopIndex::class);
});

it('turns annotated router methods into routes', function () {
    $this->application->register(new ShopModule());
    $this->application->init();

    $routes = $this->router->collection()->all();

    expect($routes)->not->toBeEmpty();
    expect($routes[0]->pattern())->toBe('/shop');
    expect($routes[0]->methods())->toContain('GET');
    expect($routes[0]->getMiddleware())->not->toBeEmpty();
    expect(reg('nr.modules'))->toHaveKey('shop');
});

it('deduplicates modules sharing a namespace', function () {
    $this->application->register(new ShopModule());
    $this->application->register(new ShopModule());

    expect($this->application->modules())->toHaveCount(1);
});
