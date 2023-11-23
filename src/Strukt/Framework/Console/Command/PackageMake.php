<?php

namespace Strukt\Framework\Console\Command;

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
		$name = str($name)->toSnake()->toCamel()->yield();

		$fs = fs(env("root_dir"));
		$pkg_file = sprintf("src/Strukt/Package/Pkg%s.php", $name);

		$msg = [];
		if(!$fs->isFile($pkg_file)){

			$msg[] = "package";
			$fs->mkdir("package");
			$fs->mkdir("src/Strukt/Package");
			
			$tpl = $fs->cat(".tpl/sgf/src/Strukt/Package/Pkg_.sgf");
			$content = template($tpl, array(

				"name"=>$name,
				"lower_name"=>strtolower($name)
			));

			$fs->touchWrite($pkg_file, $content);
		}

		if(!$fs->isFile("package.json")){

			$msg[] = "package.json";
			$pkg = array("files"=>[]);
			$pkg = json($pkg)->pp();
			$fs->touchWrite("package.json", $pkg);
		}

		$outmsg = "Already exists!";
		if(!empty($msg))
			$outmsg = sprintf("Package scaffold[%s] created successfully.", implode("|", $msg));

		$out->add($outmsg);
	}
}