<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Console\Color;
use Strukt\Framework\App as FrameworkApp;

/**
* sys:ls  List facets - providers|middlewares
* 
* Usage:
*	
*      sys:ls [<type>] [--idx]
*
* Arguments:
*
*      type     optional: (providers|middlewares)
*
* Options:
*
*      --idx -i   Flag app type
*/
class SysList extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$type = $in->get("type");

		$inputs = $in->getInputs();
		if(is_null($inputs))
			$inputs = [];

		if(array_key_exists("idx", $inputs))
			FrameworkApp::create("App:Idx");

		$types = [];
		if($type == "middlewares")
			$types[] = "middlewares";

		if($type == "providers")
			$types[] = "providers";

		if(empty($type))
			$types = array(

				"middlewares",
				"providers"
			);

		$config = FrameworkApp::getConfig();
		$lsmdl = $config->get("middlewares");
		$lsprv = $config->get("providers");

		if(in_array("middlewares", $types)){

			$out->add("\nMiddlewares\n");
			foreach($lsmdl as $facet)
				$out->add(Color::write("yellow", sprintf(" %s\n", $facet)));
		}

		if(in_array("providers", $types)){

			$out->add("\nProviders\n");
			foreach($lsprv as $facet)
				$out->add(Color::write("yellow", sprintf(" %s\n", $facet)));
		}
	}
}