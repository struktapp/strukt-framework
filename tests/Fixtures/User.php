<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Strukt\Entity;

final class User extends Entity
{
    public string $username = '';
    public string $email = '';
}