<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Fs;
use Strukt\Type\Str;
use Strukt\Type\Json;
use Strukt\Env;

/**
* package:add  Add files or folders to package.json
* 
* Usage:
*	
*      package:add <folder>
*
* Arguments:
*
*      folder     Folder or file
*/
class PackageAdd extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$folder = $in->get("folder");
		$fs = fs(env("root_dir"));

		try{

			if(!$fs->isFile("package.json")){

				$pkg = array("files"=>[]);
				$pkg = json($pkg)->pp();
				$fs->touchWrite("package.json", $pkg);
			}

			if($fs->isFile("package.json")){

				$pkg = $fs->cat("package.json");
				$pkg = json($pkg)->decode();
			}

			if($fs->isDir($folder)){

				$files = $fs->lsr($folder);
				$pkg["files"] = array_merge($pkg["files"], $files);
			}

			if($fs->isFile($folder))
				$pkg["files"][] = $folder;

			$paths = arr($pkg["files"])->each(function($k, $path){

				return ltrim(str($path)->replace(env("root_dir"), "")->yield(),"/");
			});

			$paths = array_values(array_flip(array_flip($paths->yield())));
			$pkg["files"] = $paths;
			$pkg = json($pkg)->pp();
			$fs->overwrite("package.json", $pkg);
			$fs->rm("package.json_*");

			print_r(str("\n")->concat(implode("\n", $paths))->concat("\n")->yield());
			$out->add("Files paths successfully added to package.json");
		}
		catch(\Exception $e){

			raise($e->getMessage());
		}
	}
}