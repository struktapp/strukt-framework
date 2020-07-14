<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Env;
use Strukt\Fs;
use Strukt\Util\Str;
use Strukt\Util\Json;
use Strukt\Util\Arr;
use Strukt\Templator;

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

		$vendor_pkg_path = Str::create(Env::get("root_dir"))
			->concat("/vendor/")
			->concat($package)
			->yield();

		$manifest_path = Str::create($vendor_pkg_path)
					->concat("/manifest.json")
					->yield();

		$manifest_file = Fs::cat($manifest_path);
		$manifest = Json::decode($manifest_file);

		Arr::create($manifest["files"])->each(function($key, $relpath) use ($vendor_pkg_path){

			$app_ini = parse_ini_file(Str::create(Env::get("root_dir"))
				->concat(Env::get("rel_app_ini")));

			$qpath = Str::create(Env::get("root_dir"))
				->concat("/")
				->concat($relpath);

			if($qpath->contains("app/src/App"))
				$qpath = $qpath->replace("app/src/App", sprintf("app/src/%s", $app_ini["app-name"]));

			if($qpath->endsWith(".sgf"))
				$qpath = $qpath->replace(".sgf", ".php");

			$actual_path = $qpath->yield();

			$vendor_file_path = Str::create($vendor_pkg_path)
				->concat("/package/")
				->concat($relpath)
				->yield();

			$path = pathinfo($actual_path);

			$qfilename = Str::create($path["filename"]);
			if($qfilename->startsWith("_")){

				$filename = $qfilename->replace("_", $app_ini["app-name"])->yield();
				$actual_path = $qpath->replace($path["filename"], $filename);
			}

			Fs::mkdir($path["dirname"]);

			$file_content = Fs::cat($vendor_file_path);
			if(Str::create($vendor_file_path)->endsWith(".sgf"))
				$file_content = Templator::create($file_content, array(

					"app"=>$app_ini["app-name"]
				));

			Fs::touchWrite($actual_path, $file_content);
		});
	}
}	