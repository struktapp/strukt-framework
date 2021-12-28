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
		$vars[] = "registry";
		

		if($registry->exists("app.em")){

			$em = $registry->get("app.em");
			$vars[] = "em";
		}

		if($registry->exists("app.sm")){

			$sm = $registry->get("app.sm");
			$vars[] = "sm";
		}

		if($registry->exists("app.da")){

			$da = $registry->get("app.da");
			$vars[] = "da";
		}

		if($registry->exists("core")){

			$core = $registry->get("core");
			$vars[] = "core";
		}

		$sh = new \Psy\Shell();
		$sh->setScopeVariables(compact($vars));
		$sh->run();
	}
}