<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Framework\App as FrameworkApp;
use Strukt\Console\Color;

/**
* package:ls  List packages and status
*/
class PackageList extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$packages = array_keys(FrameworkApp::getRepo());
		$published = FrameworkApp::packages("published");
		$installed = FrameworkApp::packages("installed");

		$out->add("Package Status:\n\n");

		foreach($packages as $package){

			$status = Color::write("red", "unavailable");
			if(in_array($package, $published))
				$status = Color::write("yellow", "published");
			elseif(in_array($package, $installed))
				$status = Color::write("green:bold", "installed");
			
			if($package == "core")
				continue;

			$out->add(sprintf("   %s:%s\n", $package, $status));
		}
	}
}