<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Fs;
use Strukt\Type\Str;
use Strukt\Type\Json;
use Strukt\Env;

/**
* package:copy  Copy files in package.json
* 
* Usage:
*	
*      package:copy
*/
class PackageCopy extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$root_dir = Env::get("root_dir");
		$pkgjson = Fs::cat(sprintf("%s/package.json", $root_dir));
		$package = Json::decode($pkgjson);

		$ini = parse_ini_file("cfg/app.ini");
		$app_name = $ini["app-name"];
		$ns = sprintf("namespace %s", $app_name);

		/**
		* Find models names
		*/
		$files = glob(sprintf("app/src/%s/*.php", $app_name));
		foreach($files as $file){

			$model = Str::create($file)
						->replace([sprintf("app/src/%s/", $app_name), ".php"], "")
						->yield();

			$old_nsls[] = sprintf("%s\%s", $app_name, $model);
			$new_nsls[] = sprintf("{{App}}\%s", $model);
		}

		$old_nsls[] = $ns;
		$new_nsls[] = "namespace {{App}}";

		/**
		* Copy files and change namespace
		*/
		foreach($package["files"] as $file){

			$info = pathinfo($file);
			$sDirName = Str::create($info["dirname"]);
			if($sDirName->notEquals(".")){

				$replace = sprintf("app/src/%s", $app_name);
				$dirname = $sDirName->prepend("package/")
									->replace($replace, "app/src/App")
									->yield();

				if(!Fs::isDir($dirname)){

					echo(sprintf("mkdir   |%s\n", $dirname));
					Fs::mkdir($dirname);
				}		

				$content = Str::create(Fs::cat($file))->replace($old_nsls, $new_nsls)->yield();

				$dest = sprintf("%s/%s", $dirname, $info["basename"]);
				Fs::touchWrite($dest, $content);
				echo(sprintf("copy-to |%s\n", $dest));
			}
		}

		/**
		* Refactor module classes and change their class names
		*/
		$files = glob("package/app/src/App/*Module/*Module.php");
		foreach($files as $file){

			$info = pathinfo($file);
			$basename = Str::create($info["basename"])->replace($app_name, "_")->yield();
			$rename = sprintf("%s/%s", $info["dirname"], $basename);
			Fs::rename($file, $rename);

			$file_name = basename($file);
			$module_name = basename(dirname($file));
			$content = Fs::cat($rename);
			$old_modcls_name = Str::create($app_name)->concat($module_name)->yield();
			$new_modcls_name = Str::create("{{App}}")->concat($module_name)->yield();
			$content = Str::create($content)->replace($old_modcls_name, $new_modcls_name)->yield();
			Fs::overwrite($file, $content);
			echo(sprintf("refactor|%s\n", $file));
		}

		/**
		* Clean up excess backup module classes
		*/
		Fs::rm("package/app/src/App/*Module/*Module.php_*");

		$out->add("Package files copied successfully.");
	}
}