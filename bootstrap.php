<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

$loader = require 'vendor/autoload.php';
$loader->add('App', __DIR__.'/lib/');
$loader->add('Payroll', __DIR__.'/app/src/');

\Strukt\Framework\Registry::getInstance()->set("_dir", __DIR__);