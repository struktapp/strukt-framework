<?php

declare(strict_types=1);

namespace Strukt;

/**
 * Maps constructor arguments onto a subclass's declared properties.
 *
 * ```php
 * final class User extends Entity
 * {
 *     protected string $username;
 *     protected string $email;
 * }
 *
 * $user = new User('pitsolu', 'pitsolu@gmail.com');
 * ```
 *
 * Arguments are assigned positionally to the properties in declaration order.
 * Missing arguments leave the corresponding property untouched.
 */
class Entity
{
    /**
     * @param mixed ...$args Values assigned to the declared properties, in order.
     */
    public function __construct(mixed ...$args)
    {
        $index = 0;
        foreach (array_keys(get_class_vars(get_called_class())) as $var) {
            if (array_key_exists($index, $args)) {
                $this->{$var} = $args[$index];
            }
            $index++;
        }
    }
}
