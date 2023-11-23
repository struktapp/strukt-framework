<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* package:copy  Copy files in package.json
* 
* Usage:
*	
*      package:copy
*/
class PackageCopy extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$fs = fs(env("root_dir"));
		$package = json($fs->cat("package.json"))->decode();
		$ns = sprintf("namespace %s", config("app.name"));

		/**
		* Find models names
		*/
		$app_name = config("app.name");
		$files = glob(sprintf("app/src/%s/*.php", $app_name));
		foreach($files as $file){

			$model = str($file)
						->replace([sprintf("app/src/%s/", $app_name), ".php"], "")
						->yield();

			$old_nsls[] = sprintf("%s\%s", $app_name, $model);
			$new_nsls[] = sprintf("{{app}}\%s", $model);
		}

		$old_nsls[] = $ns;
		$new_nsls[] = "namespace {{app}}";

		/**
		* Copy files and change namespace
		*/
		foreach($package["files"] as $file){

			$info = pathinfo($file);
			$dirName = str($info["dirname"]);
			if($dirName->notEquals(".")){


				$replace = sprintf("app/src/%s", $app_name);
				$dirname = $dirName->prepend("package/")
									->replace($replace, "app/src/App")
									->yield();

				if(!$fs->isDir($dirname)){

					echo(sprintf("mkdir   |%s\n", $dirname));
					$fs->mkdir($dirname);
				}

				$dest = sprintf("%s/%s", $dirname, $info["basename"]);		
			}

			if($dirName->equals("."))
				$dest = sprintf("package/%s", $info["basename"]);

			$sFileContent = str($fs->cat($file));
			foreach($old_nsls as $old_ns){

				if($sFileContent->contains($old_ns)){

					$dest = str($dest)->replace(".php", ".sgf")->yield();
					break;
				}
			}

			$content = $sFileContent->replace($old_nsls, $new_nsls)->yield();

			$fs->touchWrite($dest, $content);
			echo(sprintf("copy-to |%s\n", $dest));
		}

		/**
		* Refactor module classes and change their class names
		*/
		$files = glob("package/app/src/App/*Module/*Module.sgf");
		foreach($files as $file){

			$info = pathinfo($file);
			$basename = str($info["basename"])->replace($app_name, "_")->yield();
			$rename = sprintf("%s/%s", $info["dirname"], $basename);
			$fs->rename($file, $rename);

			$file_name = basename($rename);
			$module_name = basename(dirname($rename));
			$content = $fs->cat($rename);
			$old_modcls_name = str($app_name)->concat($module_name)->yield();
			$new_modcls_name = str("{{app}}")->concat($module_name)->yield();
			$content = str($content)->replace($old_modcls_name, $new_modcls_name)->yield();
			$fs->overwrite($rename, $content);
			echo(sprintf("refactor|%s\n", $rename));
		}

		/**
		* Clean up excess backup module classes
		*/
		$fs->rm("package/app/src/App/*Module/*Module.sgf_*");

		$out->add("Package files copied successfully.");
	}
}