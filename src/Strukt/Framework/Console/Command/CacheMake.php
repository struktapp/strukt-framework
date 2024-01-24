<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* cache:make  Cache remake
* 
* Usage:
*	
*      cache:make [<type>]
*
* Arguments:
*
*      type     optional: (idx|cli)
*/
class CacheMake extends \Strukt\Console\Command{

	public function create(string $app_type){

		return \Strukt\Framework\Configuration::create($app_type);
	}

	public function execute(Input $in, Output $out){

		$types = [

			"idx"=>"App:Idx",
			"cli"=>"App:Cli",
		];

		$app_type = $types["cli"];
		$type = $in->get("type");

		if(!empty($type)){

			if(!array_key_exists($type, $types))
				raise("Invalid option use [cli|idx]!");

			$app_type = $types[$type];
		}

		if(cache("app")->empty()){

			$settings = $this->create($app_type);
			cache("app")->put($app_type, $settings)->save();
		}

		//app.json doesn't exist
		if(empty($settings)){			

			if(!cache("app")->exists($app_type)){

				$settings = $this->create($app_type);
				cache("app")->put($app_type, $settings)->save();
			}
		}

		$out->add("Cache recreated.\n");
	}
}