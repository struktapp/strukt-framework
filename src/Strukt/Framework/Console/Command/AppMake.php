<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* app:make     Generate Application
*
* Usage:
*
*       app:make <app_name>
*
* Arguments:
*
*       app_name   application name
*                  underscored names changed to camel case
*                  example:app -> App, app_name -> AppName
*/
class AppMake extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$app_name = $in->get("app_name");
		$app_name = str($app_name)->toCamel();

		$root_dir = env("root_dir");
		$fsRead = fs($root_dir);
		$fsWrite = fs(str($root_dir)->replace("phar://","")->yield());

		$mod_ini_path = env("rel_mod_ini");
		if(!path_exists($mod_ini_path))
			raise(sprintf("Failed to find [%s] file!\n", $mod_ini_path));

		$app_src = env("rel_appsrc");
		$authmod_name = env("authmod_name");
		$authmod_dir = str(ds($app_src))
						->concat(ds($app_name))
						->concat($authmod_name);

		$fsRead->mkdir($authmod_dir->yield());
		$mod_ini = $fsRead->ini($mod_ini_path);
		arr($mod_ini["folder"])->each(function($k, $folder) use($authmod_dir, $fsRead){

			$fsRead->mkdir($authmod_dir->concat(sprintf("/%s", $folder))->yield());
		});

		$tpl_appdir = env("rel_tplapp_dir");
		$tpl_approot = env("rel_tplapproot_dir");
		$approot = str(ds($app_src))->concat($app_name)->yield();
		arr($fsRead->lsr($tpl_appdir))->each(function($k, $tpl_path) use($root_dir, 
																			$tpl_approot, 
																			$approot, 
																			$app_name,
																			$fsWrite,
																			$fsRead){

			$path = str($tpl_path)
					->replace($root_dir, "")
					->replace($tpl_approot, $approot)
					->replace(".sgf", ".php")
					->replace("_", $app_name)
					->yield();

			$tpl_path = str($tpl_path)->replace($root_dir, "")->yield();

			$output = template($fsRead->cat($tpl_path), array(

				"app"=>$app_name
			));

			$fsWrite->touchWrite(ds($path), $output);
		});

		$tpl_appini = env("rel_apptpl_ini");
		$tpl_sfgdir = env("rel_tplsgf_dir");
		$appini_path = str($tpl_appini)
						->replace(ds($tpl_sfgdir), "")
						->replace(".sgf", ".ini")
						->yield();

		$output = template($fsRead->cat($tpl_appini), array(

			"app"=>$app_name
		));

		$fsWrite->touchWrite($appini_path, $output);
		$out->add(sprintf("Successfully generated %s application!\n", $app_name));
	}
}