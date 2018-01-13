<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Loader\RegenerateModuleLoader;

/**
* generate:loader     Generate Application Loader
*/
class ApplicationLoaderGenerator extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$registry = \Strukt\Core\Registry::getInstance();

		if(!$registry->exists("dir.root"))
			throw new \Exception("Strukt root dir not defined!");

		$rootDir = $registry->get("dir.root");

		// \Strukt

		$loader = new RegenerateModuleLoader();

		$loaderDir = sprintf("%s/lib/App", $rootDir);
		// if(!\Strukt\Fs::isPath($loaderDir))
		\Strukt\Fs::mkdir($loaderDir);

		$loaderFile = sprintf("%s/Loader.php", $loaderDir);
		// if(\Strukt\Fs::isFile($loaderFile))
		\Strukt\Fs::rm($loaderFile);

		$loaderSuccess = \Strukt\Fs::touchWrite(sprintf("%s/Loader.php", $loaderDir), $loader);

		if(!$loaderSuccess)
			$out->add("***Error occured: loader generation failed!.\n");

		if($loaderSuccess)
			$out->add("Application loader generated successfully.\n");

	}
}