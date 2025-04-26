<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Console\Color;
use Strukt\Framework\Configuration;

/**
* sys:ls  List facets - providers|middlewares (system)
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

		if(config("cache.disable"))
			raise("Cache must be enabled!");

		$type = $in->get("type");

		$inputs = $in->getInputs();
		if(is_null($inputs))
			$inputs = [];

		$which = "app.App:Cli";
		if(array_key_exists("idx", $inputs))
			$which = "app.App:Idx";

		$types = [];
		if($type == "middlewares" || empty($type))
			$types[] = "middlewares";

		if($type == "providers" || empty($type))
			$types[] = "providers";

		if(in_array("middlewares", $types)){

			$out->add("\nMiddlewares\n");
			$middlewares = map(cache($which))->detach("middlewares");
			foreach($middlewares as $middleware)
				$out->add(Color::write("yellow", sprintf(" %s\n", $middleware)));
		}

		if(in_array("providers", $types)){

			$out->add("\nProviders\n");
			$providers = map(cache($which))->detach("providers");
			foreach($providers as $provider)
				$out->add(Color::write("yellow", sprintf(" %s\n", $provider)));
		}
	}
}