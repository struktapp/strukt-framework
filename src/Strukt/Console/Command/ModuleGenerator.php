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

		$registry = \Strukt\Core\Registry::getInstance();

		if(!$registry->exists("dir.root"))
			throw new \Exception("Strukt root dir not defined!");

		if(!$registry->exists("dir.app"))
			throw new \Exception("Strukt app dir not defined!");

		$rootDir = $registry->get("dir.root");
		$appDir = $registry->get("dir.app");
		$moduleList = unserialize($registry->get("module-list"));

		/**
		* Application Name
		*/
		$rawAppName = $in->get("application_name");

		if(preg_match("/^[A-Za-z0-9_]+$/", $rawAppName)){

			foreach(explode("_", $rawAppName) as $appPart)
				$appParts[] = ucfirst($appPart);

			$appName = ucfirst(implode("", $appParts));
		}
		
		if(empty($appName))
			$appName = ucfirst($rawAppName);

		/**
		* Module Name
		*/
		$rawModName = $in->get("module_name");

		$rawModName = preg_replace("/module/i", "", $rawModName);

		if(preg_match("/^[A-Za-z0-9_]+$/", $rawModName)){

			foreach(explode("_", $rawModName) as $modPart)
				$modParts[] = ucfirst($modPart);

			$modName = sprintf("%sModule", ucfirst(implode("", $modParts)));
		}

		if(empty($modName))
			$modName = sprintf("%sModule", ucfirst($rawModName));

		if(in_array(sprintf("%s%s", $appName, $modName), array_keys($moduleList)))
			throw new \Exception("Module already exists!");

		/**
		* Alias
		*/
		$rawAliasName = $in->get("alias_name");

		if(preg_match("/^[A-Za-z0-9_]+$/", $rawAliasName)){

			foreach(explode("_", $rawAliasName) as $aliasPart)
				$aliasParts[] = ucfirst($aliasPart);

			$aliasName = ucfirst(implode("", $aliasParts));
		}

		if(empty($aliasName))
			$aliasName = ucfirst($rawAliasName);

		//

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