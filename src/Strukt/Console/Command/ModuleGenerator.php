<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Generator\ClassBuilder;
use Strukt\Fs;

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

		$root_dir = Env::get("root_dir");
		$app_dir = Env::get("rel_appsrc_dir");
		$mod_ini = Env::get("rel_mod_ini");

		$registry = \Strukt\Core\Registry::getInstance();

		// if(!$registry->exists("dir.root"))
		// 	throw new \Exception("Strukt root dir not defined!");

		// if(!$registry->exists("dir.app"))
		// 	throw new \Exception("Strukt app dir not defined!");

		// $rootDir = $registry->get("dir.root");
		// $appDir = $registry->get("dir.app");
		$moduleList = unserialize($registry->get("module-list"));

		/**
		* Application Name
		*/
		$raw_app_name = $in->get("application_name");

		// if(preg_match("/^[A-Za-z0-9_]+$/", $rawAppName)){

		// 	foreach(explode("_", $rawAppName) as $appPart)
		// 		$appParts[] = ucfirst($appPart);

		// 	$appName = ucfirst(implode("", $appParts));
		// }
		
		// if(empty($appName))
		// 	$appName = ucfirst($rawAppName);

		$appName = (new Str($raw_app_name))->toCamel();

		/**
		* Module Name
		*/
		$raw_mod_name = $in->get("module_name");

		// $rawModName = preg_replace("/module/i", "", $rawModName);

		// if(preg_match("/^[A-Za-z0-9_]+$/", $rawModName)){

		// 	foreach(explode("_", $rawModName) as $modPart)
		// 		$modParts[] = ucfirst($modPart);

		// 	$modName = sprintf("%sModule", ucfirst(implode("", $modParts)));
		// }

		// if(empty($modName))
		// 	$modName = sprintf("%sModule", ucfirst($rawModName));

		$modName = new Str($raw_mod_name)->toCamel();
		if(!$modName->endsWith("Module"))
			$modName = $modName->concat("Module");

		if(in_array(sprintf("%s%s", $appName, $modName), array_keys($moduleList)))
			throw new \Exception("Module already exists!");

		/**
		* Alias
		*/
		$raw_alias_name = $in->get("alias_name");

		// if(preg_match("/^[A-Za-z0-9_]+$/", $rawAliasName)){

		// 	foreach(explode("_", $rawAliasName) as $aliasPart)
		// 		$aliasParts[] = ucfirst($aliasPart);

		// 	$aliasName = ucfirst(implode("", $aliasParts));
		// }

		// if(empty($aliasName))
		// 	$aliasName = ucfirst($rawAliasName);

		$aliasName = new Str($raw_alias_name)->toCamel();

		//

		$auth_mod_path = sprintf("%s%s%s/%s", 
								$root_dir,
								$app_dir,
								$appName,
								$modName);

		Fs::mkdir($auth_mod_path);

		$mod_ini_path = sprintf("%s/%s", $root_dir, $mod_ini);

		$mod_ini_exists = Fs::isFile($mod_ini_path);

		if($mod_ini_exists){

			$mod_ini_contents = parse_ini_file($mod_ini_path);

			if(in_array("folder", array_keys($mod_ini_contents)))
				foreach($mod_ini_contents["folder"] as $folder)
					Fs::mkdir(sprintf("%s/%s", $auth_mod_path, $folder));

			$module = new ClassBuilder(array(

				"namespace"=>sprintf("%s\%s", $appName, $modName),
				"extends"=>sprintf("\%s", \App\Module::class),
				"name"=>sprintf("%s%s", $appName, $modName)
			));

			$module->addProperty(array(

				"access"=>"protected",
				"name"=>"alias",
				"value"=>sprintf("\"%s\"", $aliasName)
			));

			Fs::touchWrite(sprintf("%s/%s%s.php", $auth_mod_path, $appName, $modName), 
							sprintf("<?php\n%s", $module));

			$out->add("Module genarated successfully.\n");
		}
		
		if(!$mod_ini_exists)
			$out->add(sprintf("Failed to find [%s] file!\n", $mod_ini));
	}
}