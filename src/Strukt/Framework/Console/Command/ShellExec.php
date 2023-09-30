<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Framework\Core;

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

		$core = new class() extends Core{

			public function __construct(){

				parent::__construct();
			}

			public function get(string $alias_ns, array $args = null){

				return parent::get($alias_ns, $args);
			}
		};

		$vars[] = "core";

		$sh = new \Psy\Shell();
		$sh->setScopeVariables(compact($vars));
		$sh->run();
	}
}