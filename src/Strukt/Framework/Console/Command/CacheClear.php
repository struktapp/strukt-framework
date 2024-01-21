<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* cache:clear     Clear cache
*/
class CacheClear extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		fs()->rmdir(".cache");

		$out->add("Cache cleared.\n");
	}
}