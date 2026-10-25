<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Console\Command\BundleAdd;
use Strukt\Framework\Console\Command\BundleExport;
use Strukt\Framework\Console\Command\BundleMake;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-pkg-' . bin2hex(random_bytes(4)));
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('scaffolds a bundle descriptor and manifest', function () {
    $root = (string) Env::get('root_dir');

    ob_start();
    $code = (new BundleMake())->execute(['name' => 'book-store'], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('created successfully');
    expect(is_file($root . '/src/Strukt/Bundle/BundleBookStore.php'))->toBeTrue();
    expect(is_file($root . '/bundle.json'))->toBeTrue();

    $source = (string) file_get_contents($root . '/src/Strukt/Bundle/BundleBookStore.php');
    expect($source)->toContain('namespace Strukt\\Bundle;');
    expect($source)->toContain('class BundleBookStore implements BundleInterface');
    expect($source)->toContain("'bundle' => 'bundle-book_store'");
});

it('reports when the scaffold already exists', function () {
    $root = (string) Env::get('root_dir');

    (new BundleMake())->execute(['name' => 'book'], []);
    Fs::touchWrite($root . '/src/Strukt/Bundle/BundleBook.php', '<?php');

    ob_start();
    $code = (new BundleMake())->execute(['name' => 'book'], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Already exists!');
});

it('adds a folder to the bundle manifest', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/bundle/tests');
    Fs::touchWrite($root . '/bundle/tests/pop.php', '<?php');
    Fs::touchWrite($root . '/bundle/Bundle.php', '<?php');

    ob_start();
    $code = (new BundleAdd())->execute(['folder' => $root . '/bundle'], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Files paths successfully added');

    $manifest = json_decode((string) file_get_contents($root . '/bundle.json'), true);
    expect($manifest['files'])->toContain('bundle/tests/pop.php');
    expect($manifest['files'])->toContain('bundle/Bundle.php');
});

it('adds a single file to the bundle manifest', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/src/Strukt/Bundle');
    Fs::touchWrite($root . '/src/Strukt/Bundle/BundleBook.php', '<?php');

    (new BundleAdd())->execute(['folder' => $root . '/src/Strukt/Bundle/BundleBook.php'], []);

    $manifest = json_decode((string) file_get_contents($root . '/bundle.json'), true);
    expect($manifest['files'])->toContain('src/Strukt/Bundle/BundleBook.php');
});

it('exports a bundle folder', function () {
    $root = (string) Env::get('root_dir');

    (new BundleMake())->execute(['name' => 'book'], []);
    Fs::touchWrite($root . '/.env', "is_dev = true\n");

    ob_start();
    $code = (new BundleExport())->execute(['name' => 'book'], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('bundle-book');

    $dir = $root . '/bundle-book';
    expect(is_file($dir . '/composer.json'))->toBeTrue();
    expect(is_file($dir . '/README.md'))->toBeTrue();
    expect(is_file($dir . '/src/Strukt/Bundle/BundleBook.php'))->toBeTrue();
    expect(is_file($dir . '/.env'))->toBeTrue();

    $composer = json_decode((string) file_get_contents($dir . '/composer.json'), true);
    expect($composer['name'])->toBe('strukt/bundle-book');
});