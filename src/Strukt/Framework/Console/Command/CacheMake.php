<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Framework\Configuration;

/**
* cache:make  Cache remake
*
* Usage:
*
*      cache:make
*/
class CacheMake extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$settings = Configuration::create("App:Idx");
		cache("app")->put("App:Idx", $settings)->save();
		
		$out->add("Cache recreated.\n");
	}
}