<?php

declare(strict_types=1);

use Strukt\Framework\Configuration;
use Strukt\Bundle\Core;
use Strukt\Framework\Contract\BundlesRepositoryInterface;

final class ConfigurationBundleStub implements BundlesRepositoryInterface
{
    public function available(): array
    {
        return ['core' => Core::class];
    }

    public function list(string $type): array
    {
        return ['core'];
    }
}

it('discovers settings through an injected bundle repository', function () {
    $settings = Configuration::create('App:Idx', new ConfigurationBundleStub());

    expect($settings['providers'])->toBeArray();
    expect($settings['middlewares'])->toContain(\Strukt\Router\Middleware\Session::class);
    expect($settings['commands'])->toBeArray();
});

it('builds a configuration with injected collaborators', function () {
    $cache = new Strukt\Cache\Cache('config-test', \Strukt\Cache\Driver\Memory::class);
    $configuration = new Configuration($cache, 'App:Idx', new ConfigurationBundleStub());

    expect($configuration->get('providers'))->toBeArray();
    expect($configuration->getAliases())->toHaveKeys(['middlewares', 'providers']);
});