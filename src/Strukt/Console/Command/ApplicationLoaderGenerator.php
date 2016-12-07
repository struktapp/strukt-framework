<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Console\Command\Helper\RegenerateModuleLoaderCommandHelper;

/**
* generate:loader     Generate Application Loader
*/
class ApplicationLoaderGenerator extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$rootDir = \Strukt\Console::getRootDir();
		if(empty($rootDir))
			throw new \Exception("Strukt root dir not defined! Use Strukt\Console::useRootDir(<root_dir>)");

		$appDir = \Strukt\Console::getAppDir();
		if(empty($appDir))
			throw new \Exception("Strukt app dir not defined! Use Strukt\Console::useAppDir(<app_dir>)");

		$loader = new RegenerateModuleLoaderCommandHelper();

		$loaderDir = sprintf("%s/lib/App", $rootDir);
		if(!\Strukt\Fs::isPath($loaderDir))
			\Strukt\Fs::mkdir($loaderDir);

		$loaderFile = sprintf("%s/Loader.php", $loaderDir);
		if(\Strukt\Fs::isFile($loaderFile))
			\Strukt\Fs::rm($loaderFile);

		$loaderSuccess = \Strukt\Fs::touchWrite(sprintf("%s/Loader.php", $loaderDir), $loader);

		if(!$loaderSuccess)
			$out->add("***Error occured: loader generation failed!.\n");

		if($loaderSuccess)
			$out->add("Application loader generated successfully.\n");

	}
}