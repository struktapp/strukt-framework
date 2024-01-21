<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Console\Color;
use Strukt\Framework\Configuration;

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
*      --idx -i   Flag for running non-cli app
*/
class SysList extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$type = $in->get("type");

		$inputs = $in->getInputs();
		if(is_null($inputs))
			$inputs = [];

		if(array_key_exists("idx", $inputs)){

			// print_r("abc");
			reg()->remove("config.app.type");
			config("app.type","App:Idx");
		}

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

		// $config = new Configuration();
		// $lsmdl = $config->get("middlewares");
		// $lsprv = $config->get("providers");

		if(in_array("middlewares", $types)){

			$out->add("\nMiddlewares\n");
			foreach(config("facet.middlewares") as $facet)
				$out->add(Color::write("yellow", sprintf(" %s\n", $facet)));
		}

		if(in_array("providers", $types)){

			$out->add("\nProviders\n");
			foreach(config("facet.providers") as $facet)
				$out->add(Color::write("yellow", sprintf(" %s\n", $facet)));
		}
	}
}