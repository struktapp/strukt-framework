<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Core\Registry;

/**
* shell:exec  Shell Mode
*/
class ShellExec extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){
		

		if(event("db.em")){

			$em = event("db.em");
			$vars[] = "em";
		}

		if(event("db.sm")){

			$sm = event("db.sm");
			$vars[] = "sm";
		}

		if(event("db.da")){

			$da = event("db.da");
			$vars[] = "da";
		}

		$sh = new \Psy\Shell();
		$sh->setScopeVariables(compact($vars));
		$sh->run();
	}
}