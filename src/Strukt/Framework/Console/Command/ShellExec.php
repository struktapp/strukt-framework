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

		$reg = Registry::getSingleton();
		$vars[] = "reg";
		

		if($reg->exists("app.em")){

			$em = $reg->get("app.em");
			$vars[] = "em";
		}

		if($reg->exists("app.sm")){

			$sm = $reg->get("app.sm");
			$vars[] = "sm";
		}

		if($reg->exists("app.da")){

			$da = $reg->get("app.da");
			$vars[] = "da";
		}

		if($reg->exists("core")){

			$core = $reg->get("core");
			$vars[] = "core";
		}

		$sh = new \Psy\Shell();
		$sh->setScopeVariables(compact($vars));
		$sh->run();
	}
}