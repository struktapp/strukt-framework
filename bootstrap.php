<?php

$sibling_dir = array();
$upper_dir = str_replace(basename(__DIR__), "", __DIR__);
foreach(scandir("../") as $folder)
	if(!preg_match("/\./", $folder))
		$sibling_dir[] = sprintf("%s%s/src", $upper_dir, $folder);

$loader = require 'vendor/autoload.php';
$loader->add('Strukt', $sibling_dir);
$loader->add('App', __DIR__.'/lib/');
$loader->add('Payroll', __DIR__.'/fixtures/root/app/src/');

\Strukt\Framework\Registry::getInstance()->set("_dir", __DIR__);