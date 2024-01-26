<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

use Strukt\Env;

$cfg_app = parse_ini_file("cfg/app.ini");

$loader = require 'vendor/autoload.php';
$loader->add('App', __DIR__.'/lib/');
$loader->add('Strukt', __DIR__.'/src/');
$loader->add($cfg_app["app-name"], __DIR__.'/app/src/');

Env::withFile();
Env::set("root_dir", getcwd());
config("app.type", "App:Idx");
reg("config.cache")->remove("disable");
reg("config.cache.disable", true);