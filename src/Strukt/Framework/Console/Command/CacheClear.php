<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Cache\Driver\Memory as MemoryCache;
use Strukt\Console\Runtime\CommandInterface;
use Strukt\Env;
use Strukt\Fs\Fs;

/**
 * @command cache:clear
 * @description Clear the cache
 * @usage cache:clear [<facet>]
 * @argument facet
 * @description Optional: (app, rtr, ctr, validation, etc..)
 */
final class CacheClear implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $facet = (string) ($arguments['facet'] ?? '');

        if ($facet === '') {
            Fs::rmdir(sprintf('%s/.cache', rtrim((string) Env::get('root_dir'), '/')));
        } else {
            $file = sprintf('%s/.cache/%s.json', rtrim((string) Env::get('root_dir'), '/'), strtolower($facet));
            if (!Fs::isFile($file)) {
                throw new \Strukt\Raise(sprintf('File [.cache/%s.json] does not exist!', $facet));
            }
            Fs::rm($file);
        }

        MemoryCache::reset();
        echo sprintf("Cache cleared%s.\n", $facet !== '' ? sprintf(' [%s.json]', $facet) : '');

        return 0;
    }
}