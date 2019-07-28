<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Core\Registry;
use Strukt\Env;
use Strukt\Fs;
/**
* publish:package     Package Publisher
*
* Usage:
*
*       publish:package <package>
*
* Arguments:
*
*       package   package name
*/
class PackagePublisher extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$package = $in->get("package");

		$app_root = Env::get("root_dir");
		$pkg_dir = sprintf("%s/vendor/%s", $app_root, $package);

		if(!Fs::isDir($pkg_dir))
			throw new \Exception(sprintf("Package [%s] not found!", $package));

		$man_file = sprintf("%s/manifest/files", $pkg_dir);

		if(!Fs::isFile($man_file))
			throw new \Exception(sprintf("Package [%s] is not a strukt module or package!", $package));

		$mod_file = sprintf("%s/manifest/modules", $app_root);

		$modules = [];

		if(Fs::isFile($mod_file)){

			$modules = explode("\n", $mod_file);

			if(!empty($modules)){

				$cfg = parse_ini_file(sprintf("%s/cfg/app.ini", $app_root));

				if(trim($cfg["app-name"]) == "__APP__")
					throw new \Exception(
						"Create an cfg[app-name] in your [cfg/app.ini] file via [generate:app cli] cmd!");
			}
		}

		$manifest_files = explode("\n", Fs::cat($man_file));

		foreach($manifest_files as $file){

			$info = pathinfo($file);

			if(array_key_exists("dirname", $info)){

				if(!empty($modules)){

					$dir = str_replace("app/src/App", 
										sprintf("app/src/%s", $cfg["app-name"]), 
										$info["dirname"]);

					$dir = sprintf("%s/%s", $app_root, $dir);
				}
				else $dir = sprintf("%s/%s", $app_root, $info["dirname"]);

				if(!Fs::isDir($dir)){

					Fs::mkdir($dir);
				}

				$o_file = sprintf("%s/%s", $dir, $info["basename"]);
				$p_file = sprintf("%s/%s/%s", $pkg_dir, $info["dirname"], $info["basename"]);

				if(Fs::isFile($o_file)){

					rename($o_file, sprintf("%s~", $o_file));
				}

				if(!empty($modules))
					$content = str_replace("__APP__", $cfg["app-name"], Fs::cat($p_file));
				else
					$content = Fs::cat($p_file);

				$file = new SplFileObject($o_file, "w+"); 
				$file->fwrite($content);
			}
		}

		foreach($modules as $module){

			$o_path = sprintf("%s/app/src/%s/%s", $app_root, $cfg["app-name"], $module);
			rename(sprintf("%s/%s.php", $o_path, $module), 
					sprintf("%s/%s%s.php", $o_path, $cfg["app-name"], $module));
		}
	}
}