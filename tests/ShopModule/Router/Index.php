<?php

declare(strict_types=1);

namespace Tests\ShopModule\Router;

use Strukt\Framework\Contract\Router;

final class Index extends Router
{
    /**
     * @Route(/shop)
     * @Method(GET)
     * @Middleware(auth)
     */
    public function index()
    {
        return 'shop';
    }
}