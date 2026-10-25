<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

use Strukt\Framework\Core;

/**
 * Base class for annotated application routers.
 *
 * A router class groups routes under one module facet, mirroring the
 * {@see \Strukt\Framework\Injectable\Router} configuration it produces.
 */
abstract class Router extends Core implements Facet
{
}
