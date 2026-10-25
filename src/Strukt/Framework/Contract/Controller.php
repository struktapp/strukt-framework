<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

use Strukt\Framework\Core;

/**
 * Base class for annotated application controllers.
 *
 * A controller exposes actions whose docblocks carry `@Route`, `@Method`, and
 * related annotations. The {@see \Strukt\Framework\Application} discovers those
 * actions and registers them with the router.
 */
abstract class Controller extends Core implements Facet
{
}
