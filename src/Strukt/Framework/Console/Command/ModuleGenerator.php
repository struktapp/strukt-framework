<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Generator\ClassBuilder;
use Strukt\Fs;
use Strukt\Framework\Contract\Module;

/**
* make:module     Generate Application Module
*
* Usage:
*
*       make:module <app_name> <module_name> <alias_name>
*
* Arguments:
*
*       app_name          your application name
*       module_name       your module name
*       alias_name        your module alias name
*/
class ModuleGenerator extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$root_dir = env("root_dir");
		$app_dir = env("rel_appsrc");
		$mod_ini = env("rel_mod_ini");

		/**
		* Application Name
		*/
		$raw_app_name = $in->get("app_name");

		$app_name = str($raw_app_name)->toCamel();

		/**
		* Module Name
		*/
		$raw_mod_name = $in->get("module_name");

		$mod_name = str($raw_mod_name)->toCamel();
		if(!$mod_name->endsWith("Module"))
			$mod_name = $mod_name->concat("Module");


		$module_cls = sprintf("%s%s", $app_name, $mod_name);
		$module_ns  = sprintf("%s\%s\%s", $app_name, $mod_name, $module_cls);

		if(in_array($module_ns, reg("nr.modules")))
			raise("Module already exists!");

		/**
		* Alias
		*/
		$raw_alias_name = $in->get("alias_name");

		$aliasName = str($raw_alias_name)->toCamel();

		//

		$auth_mod_path = sprintf("%s%s/%s", $app_dir, $app_name, $mod_name);

		Fs::mkdir(Fs::ds($auth_mod_path));
		$fs = fs($auth_mod_path);
		$mod_ini_path = sprintf("%s/%s", $root_dir, $mod_ini);

		if(Fs::isFile($mod_ini_path)){//module.ini exists

			$facets = config("module.folder");
			foreach($facets as $facet)
				$fs->mkdir($facet);

			$module = new ClassBuilder(array(

				"namespace"=>sprintf("%s\%s", $app_name, $mod_name),
				"extends"=>sprintf("\%s", Module::class),
				"name"=>sprintf("%s%s", $app_name, $mod_name)
			));

			$module->addProperty(array(

				"access"=>"protected",
				"name"=>"alias",
				"value"=>sprintf("\"%s\"", $aliasName)
			));

			$fs->touchWrite(sprintf("%s%s.php", $app_name, $mod_name), 
							sprintf("<?php\n%s", $module));

			$out->add("Module genarated successfully.\n");
		}
		
		if(!Fs::isFile($mod_ini_path))
			$out->add(sprintf("Failed to find [%s] file!\n", $mod_ini));
	}
}