<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* generate:app     Generate Application
*
* Usage:
*
*       generate:app <application_name>
*
* Arguments:
*
*       application_name   application name
*                           underscored names changed to camel case
*                            example:app -> App, app_name -> AppName
*/
class ApplicationGenerator extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$rootDir = \Strukt\Console::getRootDir();
		if(empty($rootDir))
			throw new \Exception("Strukt root dir not defined! Use Strukt\Console::useRootDir(<root_dir>)");

		$appDir = \Strukt\Console::getAppDir();
		if(empty($appDir))
			throw new \Exception("Strukt app dir not defined! Use Strukt\Console::useAppDir(<app_dir>)");

		$rawAppName = $in->get("application_name");

		$appName = ucfirst($rawAppName);
		if(preg_match("/^[A_Za-z0-9_]+$/", $rawAppName)){

			foreach(explode("_", $rawAppName) as $part)
				$parts[] = ucfirst($part);

			$appName = ucfirst(implode("", $parts));
		}

		$authModDir = sprintf("%s/%s/src/%s/AuthModule", 
								$rootDir, 
								$appDir,
								$appName);

		\Strukt\Fs::mkdir($authModDir);

		$moduleIniFile = sprintf("%s/cfg/setup/module.ini", $rootDir);

		$moduleIniFileExists = \Strukt\Fs::isFile($moduleIniFile);

		if($moduleIniFileExists){

			$moduleSettings = parse_ini_file($moduleIniFile);

			if(in_array("folder", array_keys($moduleSettings)))
				foreach($moduleSettings["folder"] as $folder)
					\Strukt\Fs::mkdir(sprintf("%s/%s", $authModDir, $folder));

			$appBase="app/src/";
			$configAppBase="cfg/sgfFiles/app/src/";
			$configModuleRoot="cfg/sgfFiles/app/src/App/AuthModule/";

			$appModuleRoot = str_replace(array($configAppBase, "App"), 
												array($appBase, $appName), 
												$configModuleRoot);

			\Strukt\Fs::mkdir($appModuleRoot);

			$files = array(

				"cfg/sgfFiles/app/src/App/AuthModule/Model/User.sgf",
				"cfg/sgfFiles/app/src/App/AuthModule/_AuthModule.sgf",
				"cfg/sgfFiles/app/src/App/AuthModule/Controller/User.sgf",
				"cfg/sgfFiles/app/src/App/AuthModule/Form/User.sgf",
				"cfg/sgfFiles/app/src/App/AuthModule/Router/Auth.sgf",
				"cfg/sgfFiles/app/src/App/AuthModule/Router/Index.sgf"
			);

			// foreach($files as $file)
				// if(!\Strukt\Fs::isFile($file))
					// throw new \Exception(sprintf("File [%s] was not found!", $file));	

			foreach($files as $file){

				if(!\Strukt\Fs::isFile($file))
					continue;
				
				$sgfFile = \Strukt\Fs::cat($file);

				$parser = new \Strukt\Generator\Parser(str_replace("__APP__", $appName, $sgfFile));
				$compiler = new \Strukt\Generator\Compiler($parser, array(

					// "excludeStandardAnnotation"=>true,
					"excludeMethodParamTypes"=>array(

						"string",
						"integer",
						"double",
						"float"
					),
					"methodAnnotationBuilder"=>function(Array $method){

						if(empty($method["annotations"]))
							return null; 
						
						foreach($method["annotations"] as $annotation){

							list($aKey, $aVal) = explode(":", $annotation, 2);

							if(strpos($aVal, "|") !== false)
								$aVal = explode("|", $aVal);

							$methAnnots[trim($aKey, "@")] = $aVal;
						}

						return new \Strukt\Generator\Annotation\Basic($methAnnots);
					}
				));

				$base = str_replace($configModuleRoot, $appModuleRoot, preg_replace("/\w+\.sgf$/", "", $file));
				if(!\Strukt\Fs::isPath($base))
					\Strukt\Fs::mkdir($base);

				\Strukt\Fs::touchWrite(str_replace(array($configModuleRoot,"sgf","_"), 
													array($appModuleRoot, "php", $appName), $file), 
														sprintf("<?php\n%s", $compiler->compile()));
			}

			$relStaticDir = \Strukt\Console::getStaticDir();

			if(!empty($relStaticDir)){

				$staticDir = sprintf("%s/%s", $rootDir, $relStaticDir);
				if(!\Strukt\Fs::isPath($staticDir))
					\Strukt\Fs::mkdir($staticDir);	
			}

			$bootstrapDir = sprintf("%s/bootstrap.php", $rootDir);
			$bootstrapContents = \Strukt\Fs::cat($bootstrapDir);
			$newBootstrapContents = str_replace(array(

					"APP_FOLDER",
					"APP_ROOT_FOLDER",
					"// "

				), array(

					$appName, 
					$appDir,
					""

				), $bootstrapContents);

			\Strukt\Fs::overwrite($bootstrapDir, $newBootstrapContents);
			
			$out->add("Application genarated successfully.\n");
		}
		
		if(!$moduleIniFileExists)
			$out->add("Failed to find [cfg/setup/module.ini] file!\n");
	}
}