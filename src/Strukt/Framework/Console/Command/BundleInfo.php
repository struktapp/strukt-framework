<?php

declare (strict_types = 1);

namespace Strukt\Framework\Console\Command;

use Strukt\Bundle\Bundles;
use Strukt\Console\Runtime\CommandInterface;

/**
 * @command bundle:info
 * @description Show information on a bundle
 * @usage bundle:info <name>
 * @argument name
 * @description Bundle name
 * @required
 */
final class BundleInfo implements CommandInterface
{
    /** Facets shown per application type, in display order. */
    private const FACETS = ['providers', 'middlewares', 'commands'];

    public function execute(array $arguments, array $options): int
    {
        $bundles   = Bundles::available();
        $installed = Bundles::list('installed');

        $name = (string) $arguments['name'];
        if ($name !== 'core' && ! array_key_exists($name, $bundles)) {
            throw new \Strukt\Raise(sprintf('Bundle [%s] does not exist!', $name));
        }

        if (! in_array($name, $installed, true)) {
            throw new \Strukt\Raise(sprintf('Bundle [%s] is not installed!', $name));
        }

        $bundle = container()->make($bundles[$name]);

        printf("Bundle:     %s\n", $bundle->getName());
        printf("Namespace:  %s\n", $bundles[$name]);
        printf("Published:  %s\n", $bundle->isPublished() ? 'Yes' : 'No');

        foreach ([
            'Requires' => $bundle->getRequirements(),
            'Modules'  => $bundle->getModules(),
            'Files'    => $bundle->getFiles(),
        ] as $label => $items) {
            if (! empty($items)) {
                printf("%-11s %s\n", $label . ':', implode(', ', $items));
            }
        }

        foreach (['App:Idx', 'App:Cli'] as $type) {
            $settings = $bundle->getSettings($type);
            if (empty($settings)) {
                printf("\n%s\n  (none)\n", $type);
                continue;
            }

            printf("\n%s\n", $type);
            foreach (self::FACETS as $facet) {
                $classes = $settings[$facet] ?? [];
                printf("  %-12s (%d)\n", ucfirst($facet), count($classes));
                foreach ($classes as $class) {
                    printf("      %s\n", $class);
                }
            }
        }

        echo "\n";

        return 0;
    }
}
