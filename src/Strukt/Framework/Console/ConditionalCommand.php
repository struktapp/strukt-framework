<?php

declare(strict_types=1);

namespace Strukt\Framework\Console;

/**
 * A console command offered only when its runtime dependency is installed.
 *
 * Conditional commands are discovered and parsed like every other command but
 * are hidden from listings (and rejected on dispatch) while
 * {@see self::isAvailable()} returns false, so optional integrations such as
 * Strukt Ar never advertise commands the installation cannot run.
 */
interface ConditionalCommand
{
    /**
     * Reports whether the command can run in the current installation.
     *
     * @return bool True when the command's dependencies are present.
     */
    public static function isAvailable(): bool;
}
