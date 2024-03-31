<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* middleware:make     Create Middlware 
*
* Usage:
*
*      middleware:make <name>
*
* Arguments:
*
*      name     Middleware name
*/
class MiddlewareMake extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$name = $in->get("name");

		$rel_mdl_sgf = env("rel_mdl_sgf");
		$rel_lib_app = env("rel_app_lib");

		$tpl_path = dirname($rel_mdl_sgf);
		$tpl_file = basename($rel_mdl_sgf);

		$tpl = fs($tpl_path)->cat($tpl_file);
		$class_name = ucfirst(str($name)->toCamel()->yield());
		$output = template($tpl, ["name"=>$name, "class_name"=>$class_name]);

		$fs = fs($rel_lib_app);
		$fs->mkdir("Middleware");
		fs(\Strukt\Fs::ds(str($rel_lib_app)->concat("/Middleware/")->yield()))
			->touchWrite(str($class_name)->concat(".php")->yield(), $output);

		$out->add("Middleware created.");
	}
}