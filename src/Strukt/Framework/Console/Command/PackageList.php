<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
// use Strukt\Framework\App as FrameworkApp;
use Strukt\Console\Color;
use Strukt\Package\Repos;

/**
* package:ls  List packages and status
*/
class PackageList extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$packages = array_keys(Repos::available());
		$published = Repos::packages("published");
		$installed = Repos::packages("installed");

		$out->add("Package Status:\n\n");

		foreach($packages as $package){

			$status = color("red", "unavailable");
			if(in_array($package, $published))
				$status = color("yellow", "published");
			elseif(in_array($package, $installed))
				$status = color("green:bold", "installed");
			
			if($package == "core")
				continue;

			$out->add(sprintf("   %s:%s\n", $package, $status));
		}
	}
}