<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* generate:module     Generate Application Module
*
* Usage:
*
*       generate:module <application_name> <module_name> <alias_name>
*
* Arguments:
*
*       application_name  your application name
*       module_name       your module name
*       alias_name       your module alias name
*/
class ModuleGenerator extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$rootDir = \Strukt\Console::getRootDir();
		if(empty($rootDir))
			throw new \Exception("Strukt root dir not defined! Use Strukt\Console::useRootDir(<root_dir>)");

		$appDir = \Strukt\Console::getAppDir();
		if(empty($appDir))
			throw new \Exception("Strukt app dir not defined! Use Strukt\Console::useAppDir(<app_dir>)");

		$rawAppName = $in->get("application_name");

		if(preg_match("/^[A-Za-z0-9_]+$/", $rawAppName)){

			foreach(explode("_", $rawAppName) as $appPart)
				$appParts[] = ucfirst($appPart);

			$appName = ucfirst(implode("", $appParts));
		}
		
		if(empty($appName))
			$appName = ucfirst($rawAppName);

		$rawModName = $in->get("module_name");
		if(preg_match("/^[A-Za-z0-9_]+$/", $rawModName)){

			foreach(explode("_", $rawModName) as $modPart)
				$modParts[] = ucfirst($modPart);

			$modName = sprintf("%sModule", ucfirst(implode("", $modParts)));
		}
		
		if(empty($modName))
			$modName = sprintf("%sModule", ucfirst($rawModName));

		$rawAliasName = $in->get("alias_name");
		if(preg_match("/^[A-Za-z0-9_]+$/", $rawAliasName)){

			foreach(explode("_", $rawAliasName) as $aliasPart)
				$aliasParts[] = ucfirst($aliasPart);

			$aliasName = ucfirst(implode("", $aliasParts));
		}
		
		if(empty($aliasName))
			$aliasName = ucfirst($rawAliasName);

		$authMod = sprintf("%s/%s/src/%s/%s", 
								$rootDir,
								$appDir,
								$appName,
								$modName);

		\Strukt\Fs::mkdir($authMod);

		$modIniFile = sprintf("%s/cfg/module.ini", $rootDir);

		$modIniFileExists = \Strukt\Fs::isFile($modIniFile);

		if($modIniFileExists){

			$modSettings = parse_ini_file($modIniFile);

			if(in_array("folder", array_keys($modSettings)))
				foreach($modSettings["folder"] as $folder)
					\Strukt\Fs::mkdir(sprintf("%s/%s", $authMod, $folder));

			$module = new \Strukt\Generator\ClassBuilder(array(

				"namespace"=>sprintf("%s\%s", $appName, $modName),
				"extends"=>"\App\Module",
				"name"=>sprintf("%s%s", $appName, $modName)
			));

			$module->addProperty(array(

				"access"=>"protected",
				"name"=>"alias",
				"value"=>sprintf("\"%s\"", $aliasName)
			));

			\Strukt\Fs::touchWrite(sprintf("%s/%s%s.php", $authMod, $appName, $modName) , sprintf("<?php\n%s", $module));

			$out->add("Module genarated successfully.\n");
		}
		
		if(!$modIniFileExists)
			$out->add("Failed to find [cfg/module.ini] file!\n");
	}
}