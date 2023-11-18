<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* app:make     Generate Application
*
* Usage:
*
*       app:make <application_name>
*
* Arguments:
*
*       application_name   application name
*                          underscored names changed to camel case
*                          example:app -> App, app_name -> AppName
*/
class ApplicationGenerator extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$raw_app_name = $in->get("application_name");

		$app_name = str($raw_app_name)->toCamel();

		$root_dir = env("root_dir");
		$app_dir = env("rel_appsrc_dir");
		$mod_ini = env("rel_mod_ini");
		$app_ini = env("rel_app_ini");
		$tpl_app_ini = env("rel_apptpl_ini");
		$tpl_app_dir = env("rel_tplapp_dir");
		$tpl_appsrc_dir = env("rel_tplappsrc_dir");
		$tpl_authmod_dir = env("rel_tplauthmod_dir");

		$auth_mod_path = str($app_dir)
			->concat($app_name)
			->concat("/AuthModule");

		$mod_ini_path = str(env("root_dir"))
			->concat(sprintf("/%s", $mod_ini))
			->yield();

		$fs_root = fs(env("root_dir"));
		$fsCfgCache = fs(".cache/cfg");
		if(!$fsCfgCache->isFile("cfg.php")){

			if(!$fs_root->isFile($mod_ini))
				raise(sprintf("Failed to find [%s] file!\n", $mod_ini_path));

			$mod_ini_contents = $fs_root->ini($mod_ini);
		}

		if($fsCfgCache->isFile("cfg.php"))
			$mod_ini_contents["folder"] = config("module.folder*"); 

		if(in_array("folder", array_keys($mod_ini_contents)))
			arr($mod_ini_contents["folder"])->each(function($k, $folder) use($fs_root, $auth_mod_path){

				$fs_root->mkdir($auth_mod_path->concat(sprintf("/%s", $folder))->yield());
			});

		$authmod_dir = str($tpl_authmod_dir)
			->replace([$tpl_appsrc_dir, "App"],[$app_dir, $app_name])
			->yield();

		$fs_root->mkdir($authmod_dir);

		$fsFilesCache = fs(".cache/files");
		if(!$fsFilesCache->isFile("tpl_app.php"))
			$files = array_flip($fs_root->lsr($tpl_app_dir));

		if($fsFilesCache->isFile("tpl_app.php"))
			$files = $fsFilesCache->req("tpl_app.php");			

		foreach($files as $file=>$tpl_file){

			$file = str($file)->replace(env("root_dir"),"")->yield();

			if(is_numeric($tpl_file)){
				
				if(!$fs_root->isFile($file))
					continue;
				
				$tpl_file = $fs_root->cat($file);
			}

			$output = template($tpl_file, array(

				"app"=>$app_name->yield()
			));

			$base = str(preg_replace("/\w+\.sgf$/", "", $file))
						->replace($tpl_authmod_dir, $authmod_dir)
						->yield();

			if(!$fs_root->isPath($base))
				$fs_root->mkdir($base);

			$outputfile = str($file)
				->replace(str(".tpl/")->concat("sgf/")->yield(),"")
				->replace("/App/", sprintf("/%s/", $app_name->yield()))
				->replace(".sgf", ".php");

			if($outputfile->contains("_AuthModule.php")){

				$module_name = sprintf("%sAuthModule.php", $app_name->yield());
				$outputfile = $outputfile->replace("_AuthModule.php", $module_name);
			}

			$outputfile = $outputfile->yield();
			if(!$fs_root->touchWrite($outputfile, $output))
				raise(sprintf("%s did not generate!", $outputfile));
		}

		if(!$fs_root->isFile($tpl_app_ini))
			raise(sprintf("Failed to find [%s] file!\n", $tpl_app_ini));

		$tpl_app_ini_content = $fs_root->cat($tpl_app_ini);
		$app_ini_output = template($tpl_app_ini_content, array(

			"app"=>$app_name->yield()
		));

		$fs_root->touchWrite($app_ini, $app_ini_output);
		$out->add(sprintf("Successfully generated %s application!\n", $app_name->yield()));
	}
}