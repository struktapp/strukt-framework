<?php

namespace Strukt\Console\Command;

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

		if(!Fs::isFile("package.json")){

			$pkg = array("files"=>[]);
			$pkg = Json::pp($pkg);
			Fs::touchWrite("package.json", $pkg);
		}

		if(Fs::isFile("package.json")){

			$pkg = Fs::cat("package.json");
			$pkg = Json::decode($pkg);
		}

		if(Fs::isDir($folder)){

			$files = Fs::lsr($folder);
			$pkg["files"] = array_merge($pkg["files"], $files);
		}

		if(Fs::isFile($folder))
			$pkg["files"][] = $folder;

		$pkg = Json::pp($pkg);
		Fs::overwrite("package.json", $pkg);
		Fs::rm("package.json_*");
	}
}