<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Bundle\Bundles;
use Strukt\Console\Runtime\CommandInterface;

/**
 * @command bundle:ls
 * @description List bundles and their status
 */
final class BundleList implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $bundles = array_keys(Bundles::available());
        $published = Bundles::list('published');
        $installed = Bundles::list('installed');

        echo "Bundle Status:\n\n";

        foreach ($bundles as $bundle) {
            if ($bundle === 'core') {
                continue;
            }

            $status = 'unavailable';
            if (in_array($bundle, $published, true)) {
                $status = 'published';
            } elseif (in_array($bundle, $installed, true)) {
                $status = 'installed';
            }

            printf("   %s:%s\n", $bundle, $status);
        }

        return 0;
    }
}
