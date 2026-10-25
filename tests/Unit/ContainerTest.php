<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Strukt\Container\Container;
use Strukt\Container\ContainerException;
use Strukt\Container\NotFoundException;

final class ContainerDependencyStub
{
}

final class ContainerServiceStub
{
    public function __construct(public ContainerDependencyStub $dependency)
    {
    }
}

final class ContainerUnresolvableStub
{
    public function __construct(public $unresolved)
    {
    }
}

abstract class ContainerUninstantiableStub
{
}

it('is a PSR-11 container', function () {
    expect(new Container())->toBeInstanceOf(ContainerInterface::class);
});

it('resolves shared bindings to the same instance', function () {
    $container = new Container();
    $container->set(ContainerServiceStub::class, static fn () => new ContainerServiceStub(new ContainerDependencyStub()));

    expect($container->get(ContainerServiceStub::class))->toBe($container->get(ContainerServiceStub::class));
});

it('resolves transient bindings to fresh instances', function () {
    $container = new Container();
    $container->factory(ContainerDependencyStub::class, static fn () => new ContainerDependencyStub());

    expect($container->get(ContainerDependencyStub::class))->not->toBe($container->get(ContainerDependencyStub::class));
});

it('autowires constructor dependencies', function () {
    $service = (new Container())->make(ContainerServiceStub::class);

    expect($service)->toBeInstanceOf(ContainerServiceStub::class);
    expect($service->dependency)->toBeInstanceOf(ContainerDependencyStub::class);
});

it('honours named constructor arguments', function () {
    $dependency = new ContainerDependencyStub();
    $service = (new Container())->make(ContainerServiceStub::class, ['dependency' => $dependency]);

    expect($service->dependency)->toBe($dependency);
});

it('reports whether an identifier is resolvable', function () {
    $container = new Container();
    $container->set('shared', static fn () => new stdClass());

    expect($container->has('shared'))->toBeTrue();
    expect($container->has('missing'))->toBeFalse();
});

it('throws a not-found exception for unknown identifiers', function () {
    (new Container())->get('missing.identifier');
})->throws(NotFoundException::class);

it('throws a container exception for unresolvable parameters', function () {
    (new Container())->make(ContainerUnresolvableStub::class, []);
})->throws(ContainerException::class);

it('throws a container exception for non-instantiable classes', function () {
    (new Container())->make(ContainerUninstantiableStub::class);
})->throws(ContainerException::class);