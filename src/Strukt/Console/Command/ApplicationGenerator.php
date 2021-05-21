<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Env;
use Strukt\Type\Str;
use Strukt\Fs;
use Strukt\Templator;
use Strukt\Raise;

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

		$raw_app_name = $in->get("application_name");

		$app_name = Str::create($raw_app_name)->toCamel();

		$root_dir = Env::get("root_dir");
		$app_dir = Env::get("rel_appsrc_dir");
		$mod_ini = Env::get("rel_mod_ini");
		$app_ini = Env::get("rel_app_ini");
		$tpl_app_ini = Env::get("rel_apptpl_ini");
		$tpl_app_dir = Env::get("rel_tplapp_dir");
		$tpl_appsrc_dir = Env::get("rel_tplappsrc_dir");
		$tpl_authmod_dir = Env::get("rel_tplauthmod_dir");

		$auth_mod_path = sprintf("%s".DS."%s%s".DS."AuthModule", 
								$root_dir,
								$app_dir,
								$app_name);

		$mod_ini_path = sprintf("%s".DS."%s", $root_dir, $mod_ini);

		if(!Fs::isFile($mod_ini_path))
			new Raise(sprintf("Failed to find [%s] file!\n", $mod_ini_path));

		$mod_ini_contents = parse_ini_file($mod_ini_path);

		if(in_array("folder", array_keys($mod_ini_contents)))
			foreach($mod_ini_contents["folder"] as $folder)
				Fs::mkdir(sprintf("%s".DS."%s", $auth_mod_path, $folder));

		$authmod_dir = str_replace(array($tpl_appsrc_dir, "App"), 
											array($app_dir, $app_name), 
											$tpl_authmod_dir);

		Fs::mkdir($authmod_dir);
		
		$files = Fs::lsr($tpl_app_dir);

		foreach($files as $file){

			if(!Fs::isFile($file))
				continue;
			
			$tpl_file = Fs::cat($file);

			$output = Templator::create($tpl_file, array(

				"app"=>$app_name->yield()
			));

			$base = str_replace($tpl_authmod_dir, $authmod_dir, 
									preg_replace("/\w+\.sgf$/", "", $file));

			if(!Fs::isPath($base))
				Fs::mkdir($base);

			$outputfile = Str::create($file)
				->replace("tpl".DS."sgf".DS,"")
				->replace(DS."App".DS, sprintf(DS."%s".DS, $app_name->yield()))
				->replace(".sgf", ".php");	

			if($outputfile->contains("_AuthModule.php")){

				$module_name = sprintf("%sAuthModule.php", $app_name->yield());
				$outputfile = $outputfile->replace("_AuthModule.php", $module_name);
			}

			$outputfile = $outputfile->yield();



			if(!Fs::touchWrite($outputfile, $output))
				new Raise(sprintf("%s did not generate!", $outputfile));
		}

		if(!Fs::isFile($tpl_app_ini))
			new Raise(sprintf("Failed to find [%s] file!\n", $tpl_app_ini));

		$tpl_app_ini_content = Fs::cat($tpl_app_ini);

		$app_ini_output = Templator::create($tpl_app_ini_content, array(

			"app"=>$app_name->yield()
		));

		Fs::touchWrite($app_ini, $app_ini_output);
		
		$out->add(sprintf("Successfully generated %s application!\n", $app_name->yield()));
	}
}