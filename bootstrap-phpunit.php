<?php

use Strukt\Core\Registry;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

$appCfg = parse_ini_file("cfg/app.ini");

$loader = require 'vendor/autoload.php';
$loader->add('App', __DIR__.'/lib/');
// $loader->add('Strukt', __DIR__.'/../strukt-commons/src/');
// $loader->add('Strukt', __DIR__.'/../strukt-router/src/');
$loader->add($appCfg["app-name"], __DIR__.'/app/src/');

$registry = Registry::getInstance();
$registry->set("_dir", __DIR__);