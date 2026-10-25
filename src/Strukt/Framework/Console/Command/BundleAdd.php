<?php

declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Fs\Fs;

/**
 * @command bundle:add
 * @description Add files or folders to bundle.json
 * @usage bundle:add <folder>
 * @argument folder
 * @description Folder or file
 * @required
 */
final class BundleAdd implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $root = (string) env('root_dir');
        $folder = (string) $arguments['folder'];

        $pkgJson = sprintf('%s/bundle.json', $root);
        if (!Fs::isFile($pkgJson)) {
            Fs::touchWrite($pkgJson, self::jsonPretty(['files' => []]));
            $manifest = ['files' => []];
        } else {
            $manifest = json_decode((string) Fs::cat($pkgJson), true) ?: ['files' => []];
        }

        $source = self::resolve($root, $folder);
        if (Fs::isDir($source)) {
            $paths = Fs::lsr($source);
        } elseif (Fs::isFile($source)) {
            $paths = [$source];
        } else {
            throw new \Strukt\Raise(sprintf('Path [%s] does not exist!', $folder));
        }

        foreach ($paths as $path) {
            $relative = ltrim(str_replace($root, '', $path), '/');
            if (!in_array($relative, $manifest['files'], true)) {
                $manifest['files'][] = $relative;
            }
        }

        Fs::overwrite($pkgJson, self::jsonPretty($manifest));

        foreach ($manifest['files'] as $file) {
            echo "  {$file}\n";
        }

        echo "Files paths successfully added to bundle.json\n";

        return 0;
    }

    /**
     * Resolves a possibly relative source path against the project root.
     *
     * @param string $root Project root directory.
     * @param string $path Path as provided by the caller.
     * @return string Absolute path.
     */
    private static function resolve(string $root, string $path): string
    {
        if (str_starts_with($path, $root)) {
            return $path;
        }

        return sprintf('%s/%s', $root, ltrim($path, '/'));
    }

    private static function jsonPretty(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}