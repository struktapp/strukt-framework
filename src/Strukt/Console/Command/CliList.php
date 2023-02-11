<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Console\Color;
use Strukt\Framework\App as FrameworkApp;

/**
* cli:ls  List facets - providers|middlewares
* 
* Usage:
*	
*      cli:ls <type> [--idx]
*
* Arguments:
*
*      type     options: (providers|middlewares)
*
* Options:
*
*      --idx -i   Flag app type
*/
class CliList extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$type = $in->get("type");

		if(array_key_exists("idx", $in->getInputs()))
			FrameworkApp::create("App:Idx");

		$config = FrameworkApp::getConfig();
		$ls = $config->get($type);

		$out->add("\n");
		foreach($ls as $facet)
			$out->add(Color::write("yellow", sprintf(" %s\n", $facet)));
	}
}