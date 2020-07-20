<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Generator\ClassBuilder;
use Strukt\Fs;
use Strukt\Env;
use Strukt\Util\Str;
use Strukt\Core\Registry as Registry;

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

		$registry = Registry::getSingleton();

		$moduleList = unserialize($registry->get("module-list"));

		/**
		* Application Name
		*/
		$raw_app_name = $in->get("application_name");

		$app_name = Str::create($raw_app_name)->toCamel();

		/**
		* Module Name
		*/
		$raw_mod_name = $in->get("module_name");

		$mod_name = Str::create($raw_mod_name)->toCamel();
		if(!$mod_name->endsWith("Module"))
			$mod_name = $mod_name->concat("Module");

		if(in_array(sprintf("%s%s", $app_name, $mod_name), array_keys($moduleList)))
			throw new \Exception("Module already exists!");

		/**
		* Alias
		*/
		$raw_alias_name = $in->get("alias_name");

		$aliasName = Str::create($raw_alias_name)->toCamel();

		//

		$auth_mod_path = sprintf("%s/%s%s/%s", 
								$root_dir,
								$app_dir,
								$app_name,
								$mod_name);

		Fs::mkdir($auth_mod_path);

		$mod_ini_path = sprintf("%s/%s", $root_dir, $mod_ini);

		$mod_ini_exists = Fs::isFile($mod_ini_path);

		if($mod_ini_exists){

			$mod_ini_contents = parse_ini_file($mod_ini_path);

			if(in_array("folder", array_keys($mod_ini_contents)))
				foreach($mod_ini_contents["folder"] as $folder)
					Fs::mkdir(sprintf("%s/%s", $auth_mod_path, $folder));

			$module = new ClassBuilder(array(

				"namespace"=>sprintf("%s\%s", $app_name, $mod_name),
				"extends"=>sprintf("\%s", \Strukt\Contract\Module::class),
				"name"=>sprintf("%s%s", $app_name, $mod_name)
			));

			$module->addProperty(array(

				"access"=>"protected",
				"name"=>"alias",
				"value"=>sprintf("\"%s\"", $aliasName)
			));

			Fs::touchWrite(sprintf("%s/%s%s.php", $auth_mod_path, $app_name, $mod_name), 
							sprintf("<?php\n%s", $module));

			$out->add("Module genarated successfully.\n");
		}
		
		if(!$mod_ini_exists)
			$out->add(sprintf("Failed to find [%s] file!\n", $mod_ini));
	}
}