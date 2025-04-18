<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Generator\ClassBuilder;
use Strukt\Fs;
use Strukt\Framework\Contract\Module;

/**
* module:make     Generate Application Module
*
* Usage:
*
*       module:make <app_name> <module_name> <alias_name>
*
* Arguments:
*
*       app_name          Application name
*       module_name       Module name
*       alias_name        Module alias
*/
class ModuleMake extends \Strukt\Console\Command{

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
		$modules = map(reg("nr"))->detach("modules");
		
		if(in_array($module_ns, $modules))
			raise("Module already exists!");

		/**
		* Alias
		*/
		$raw_alias_name = $in->get("alias_name");
		$aliasName = str($raw_alias_name)->toCamel();

		//
		$auth_mod_path = str(ds($app_dir))
			->concat(ds($app_name))
			->concat(ds($mod_name))
			->yield();

		Fs::mkdir($auth_mod_path);

		$mod_ini_path = str(ds($root_dir))->concat($mod_ini)->yield();

		$fsModule = fs($auth_mod_path);
		$fsRoot = fs($root_dir);
		if($fsRoot->isFile($mod_ini)){//module.ini exists

			$facets = map(config("module"))->detach("folders");
			foreach($facets as $facet)
				$fsModule->mkdir($facet);

			$module = generator(["declaration"=>[
				"namespace"=>sprintf("%s\%s", $app_name, $mod_name),
				"extends"=>sprintf("\%s", Module::class),
				"name"=>sprintf("%s%s", $app_name, $mod_name)
			]]);

			$module->property(array(
				"access"=>"protected",
				"name"=>"alias",
				"value"=>sprintf("\"%s\"", $aliasName)
			));

			$fsModule->touchWrite(sprintf("%s%s.php", $app_name, $mod_name), sprintf("<?php\n%s", $module));
			$out->add("Module genarated successfully.\n");
		}
		
		if(negate($fsRoot->isFile($mod_ini)))
			$out->add(sprintf("Failed to find [%s] file!\n", $mod_ini));
	}
}