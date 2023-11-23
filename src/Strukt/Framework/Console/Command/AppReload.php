<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Framework\Reloader;
use Strukt\Env;
use Strukt\Fs;

/**
* app:reload     Generate Application Loader
*/
class AppReload extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$root_dir = env("root_dir");
		$app_lib = env("rel_app_lib");

		$fs = fs($root_dir);
		$fs->mkdir($app_lib);
		$loader_file = sprintf("%s/Loader.php", $app_lib);
		if($fs->isFile($loader_file))
			$fs->rm($loader_file);

		$loader = (string) new Reloader();
		$is_loader_created = $fs->touchWrite($loader_file, $loader);

		if(!$is_loader_created)
			$out->add("***Error occured: loader generation failed!.\n");
		else
			$out->add("Application loader generated successfully.\n");

	}
}