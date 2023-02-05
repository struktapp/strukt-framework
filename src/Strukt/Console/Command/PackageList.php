<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Framework\App as FrameworkApp;

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

			$status = "unavailable";
			if(in_array($package, $published))
				$status = "published";
			elseif(in_array($package, $installed))
				$status = "installed";
			
			if($package == "core")
				continue;

			$out->add(sprintf("   %s:%s\n", $package, $status)); 
		}
	}
}