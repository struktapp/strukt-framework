<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* app:extend     Extend Core Package Settings
*/
class AppExtender extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$rel_pkg_ext = env("rel_pkg_ext");
		
		$ext = fs()->cat($rel_pkg_ext);
		fs("lib")->mkdir("Strukt/Package");
		fs("lib/Strukt/Package")->touchWrite("Extender.php", $ext);

		$out->add("Created Extender@lib/Strukt/Package.");
	}
}