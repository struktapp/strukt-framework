<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

$cfg_app = parse_ini_file("cfg/app.ini");

$loader = require 'vendor/autoload.php';
$loader->add('App', __DIR__.'/lib/');
$loader->add('Strukt', __DIR__.'/src/');
$loader->add($cfg_app["app-name"], __DIR__.'/app/src/');