<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

// $sibling_dir = array();
// $upper_dir = str_replace(basename(__DIR__), "", __DIR__);
// foreach(scandir("../") as $folder)
// 	if(!preg_match("/\./", $folder))
// 		$sibling_dir[] = sprintf("%s%s/src", $upper_dir, $folder);

$loader = require 'vendor/autoload.php';
// $loader->add('Strukt', $sibling_dir);
$loader->add('App', __DIR__.'/lib/');
$loader->add('Payroll', __DIR__.'/app/src/');

\Strukt\Framework\Registry::getInstance()->set("_dir", __DIR__);