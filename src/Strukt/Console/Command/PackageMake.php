<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Fs;
use Strukt\Type\Str;
use Strukt\Type\Json;
use Strukt\Env;
use Strukt\Templator;

/**
* package:make  Create Package Scaffold
* 
* Usage:
*	
*      package:make <name>
*
* Arguments:
*
*      name     Package Name
*/
class PackageMake extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$name = $in->get("name");
		$name = Str::create($name)->toSnake()->toCamel()->yield();

		$root_dir = Env::get("root_dir");

		$pkg_file = sprintf("%s/src/Strukt/Package/Pkg%s.php", $root_dir, $name);

		$msg = [];
		if(!Fs::isFile($pkg_file)){

			$msg[] = "package";
			Fs::mkdir(sprintf("%s/package", $root_dir));
			Fs::mkdir(sprintf("%s/src/Strukt/Package", $root_dir));
			
			$tpl = Fs::cat("tpl/sgf/src/Strukt/Package/Pkg_.sgf");
			$content = Templator::create($tpl, array(

				"name"=>$name,
				"lower_name"=>strtolower($name)
			));

			Fs::touchWrite($pkg_file, $content);
		}

		if(!Fs::isFile("package.json")){

			$msg[] = "package.json";
			$pkg = array("files"=>[]);
			$pkg = Json::pp($pkg);
			Fs::touchWrite("package.json", $pkg);
		}

		$outmsg = "Already exists!";
		if(!empty($msg))
			$outmsg = sprintf("Package scaffold[%s] created successfully.", implode("|", $msg));

		$out->add($outmsg);
	}
}