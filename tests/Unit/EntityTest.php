<?php

declare(strict_types=1);

use Tests\Fixtures\User;

it('assigns constructor arguments to declared properties positionally', function () {
    $user = new User('pitsolu', 'pitsolu@gmail.com');

    expect($user->username)->toBe('pitsolu');
    expect($user->email)->toBe('pitsolu@gmail.com');
});

it('leaves a property untouched when the argument is missing', function () {
    $user = new User('pitsolu');

    expect($user->username)->toBe('pitsolu');
    expect($user->email)->toBe('');
});

it('assigns values in declaration order, not argument order', function () {
    $user = new User('a@b.c', 'login');

    expect($user->username)->toBe('a@b.c');
    expect($user->email)->toBe('login');
});