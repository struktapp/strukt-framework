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

		$root_dir = Env::get("root_dir");
		$app_lib = Env::get("rel_app_lib");

		$loader_dir = sprintf("%s/%s", $root_dir, $app_lib);
		
		Fs::mkdir($loader_dir);

		$loader_file = sprintf("%s/Loader.php", $loader_dir);
		
		if(Fs::isFile($loader_file))
			Fs::rm($loader_file);

		$is_loader_created = Fs::touchWrite($loader_file, new Reloader());

		if(!$is_loader_created)
			$out->add("***Error occured: loader generation failed!.\n");
		else
			$out->add("Application loader generated successfully.\n");

	}
}