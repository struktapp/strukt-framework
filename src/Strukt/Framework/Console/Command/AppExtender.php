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
		if(fs(str("src/Strukt/Package"))->isFile("Extender.php"))
			raise("Strukt\\Package\\Extender already exists!");
		
		$ext = fs()->cat($rel_pkg_ext);
		fs()->mkdir("src/Strukt/Package");
		fs("src/Strukt/Package")->touchWrite("Extender.php", $ext);

		$out->add("Created Extender@src/Strukt/Package.");
	}
}