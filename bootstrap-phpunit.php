<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

use Strukt\Env;

$cfg_app = parse_ini_file("cfg/app.ini");

$loader = require 'vendor/autoload.php';
$loader->add('App', __DIR__.'/lib/');
$loader->add('Strukt', __DIR__.'/src/');
$loader->add($cfg_app["app-name"], __DIR__.'/app/src/');

Env::set("root_dir", getcwd());
Env::set("rel_app_ini", "cfg/app.ini");
Env::set("rel_mod_ini", "cfg/module.ini");
Env::set("rel_static_dir", "public/static");
Env::set("is_dev", true);