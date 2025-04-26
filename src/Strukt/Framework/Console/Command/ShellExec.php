<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Framework\Core as Framework;

/**
* shell:exec  Shell Mode (sh)
*/
class ShellExec extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$core = new class() extends Framework{

			public function get(string $alias_ns, array $args = null):object{

				return parent::get($alias_ns, $args);
			}
		};

		$vars[] = "core";

		$sh = new \Psy\Shell();
		$sh->setScopeVariables(compact($vars));
		$sh->run();
	}
}