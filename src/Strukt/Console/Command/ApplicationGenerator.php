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

		$registry = \Strukt\Core\Registry::getInstance();

		if(!$registry->exists("dir.root"))
			throw new \Exception("Strukt root dir not defined!");

		if(!$registry->exists("dir.app"))
			throw new \Exception("Strukt app dir not defined!");

		$rawAppName = $in->get("application_name");

		$appName = ucfirst($rawAppName);
		if(preg_match("/^[A_Za-z0-9_]+$/", $rawAppName)){

			foreach(explode("_", $rawAppName) as $part)
				$parts[] = ucfirst($part);

			$appName = ucfirst(implode("", $parts));
		}

		$rootDir = $registry->get("dir.root");
		$appDir = $registry->get("dir.app");

		$authModDir = sprintf("%s/%s/src/%s/AuthModule", 
								$rootDir, 
								$appDir,
								$appName);

		\Strukt\Fs::mkdir($authModDir);

		$moduleIniFile = sprintf("%s/cfg/module.ini", $rootDir);

		$moduleIniFileExists = \Strukt\Fs::isFile($moduleIniFile);

		if($moduleIniFileExists){

			$moduleSettings = parse_ini_file($moduleIniFile);

			if(in_array("folder", array_keys($moduleSettings)))
				foreach($moduleSettings["folder"] as $folder)
					\Strukt\Fs::mkdir(sprintf("%s/%s", $authModDir, $folder));

			$appBase="app/src/";
			$configAppBase="tpl/sgf/app/src/";
			$configModuleRoot="tpl/sgf/app/src/App/AuthModule/";

			$appModuleRoot = str_replace(array($configAppBase, "App"), 
												array($appBase, $appName), 
												$configModuleRoot);

			\Strukt\Fs::mkdir($appModuleRoot);

			$files = \Strukt\Fs::lsr("tpl/sgf/app");	

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

			$appIni = \Strukt\Fs::cat("cfg/app.ini");
			$newAppIni = str_replace("__APP__", $appName, $appIni);
			\Strukt\Fs::overwrite("cfg/app.ini", $newAppIni);
			
			$out->add("Application genarated successfully.\n");
		}
		
		if(!$moduleIniFileExists)
			$out->add("Failed to find [cfg/module.ini] file!\n");
	}
}