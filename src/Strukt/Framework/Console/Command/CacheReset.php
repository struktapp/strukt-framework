<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* cache:reset  Cache reset (cache)
*/
class CacheReset extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$ps = process([

			"./xcli cache:clear",
			"./xcli cache:make"
		]);
	}
}