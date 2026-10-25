<?php

declare(strict_types=1);

namespace Strukt\Framework\Traits;

use Strukt\Raise;
use Strukt\Ref;

/**
 * Resolves application class names and instantiates them without a constructor.
 */
trait ClassHelper
{
    /**
     * Resolves the `{{app}}` placeholder in a class name.
     *
     * @param string $class Class name, optionally containing `{{app}}`.
     * @return string Resolved class name.
     */
    public function getClass(string $class): string
    {
        if (str_contains($class, '{{app}}')) {
            $class = str_replace('{{app}}', (string) config('app.name'), $class);
        }

        return $class;
    }

    /**
     * Instantiates a class without running its constructor.
     *
     * @param string $class Class name, optionally containing `{{app}}`.
     * @return object Instance of the class.
     *
     * @throws Raise When the class does not exist.
     */
    public function newClass(string $class): object
    {
        $class = $this->getClass($class);
        if (!class_exists($class)) {
            throw new Raise(sprintf('%s does not exist!', $class));
        }

        return Ref::create($class)->noMake()->getInstance();
    }
}
