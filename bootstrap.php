<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

$appCfg = parse_ini_file("cfg/app.ini");

$loader = require 'vendor/autoload.php';
$loader->add('App', __DIR__.'/lib/');
$loader->add($appCfg["app-name"], __DIR__.'/app/src/');
\Strukt\Framework\Registry::getInstance()->set("_dir", __DIR__);