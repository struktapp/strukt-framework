<?php

declare(strict_types=1);

use App\Loader;
use Strukt\Framework\Application;
use Strukt\Framework\Autoload;

require __DIR__ . '/vendor/autoload.php';

Autoload::register(__DIR__);

if (PHP_SAPI === 'cli-server') {
    $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

$app = class_exists(Loader::class)
    ? (new Loader())->getApp()
    : Application::make();

$app->run();