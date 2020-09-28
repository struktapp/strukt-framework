<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Core\Registry;

/**
* shell:exec  Shell Mode
*/
class ShellExec extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$registry = Registry::getSingleton();

		if($registry->exists("app.em"))
			$em = $registry->get("app.em");

		if($registry->exists("app.sm"))
			$sm = $registry->get("app.sm");

		if($registry->exists("app.da"))
			$da = $registry->get("app.da");

		if($registry->exists("core"))
			$core = $registry->get("core");

		$sh = new \Psy\Shell();
		$sh->setScopeVariables(compact('core', 'sm', 'em', 'da', 'registry'));
		$sh->run();
	}
}