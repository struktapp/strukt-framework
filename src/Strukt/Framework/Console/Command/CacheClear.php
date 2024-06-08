<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* cache:clear  Cache clear
* 
* Usage:
*	
*      cache:clear [<facet>]
*
* Arguments:
*
*      facet     optional: (app, rtr, ctr, validation, etc..)
*/
class CacheClear extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$facet = $in->get("facet");
		if(is_null($facet))
			fs()->rmdir(".cache");

		if(notnull($facet)){

			if(negate(fs(".cache")->isFile(sprintf("%s.json", $facet))))
				raise(sprintf("File [.cache/%s.json] does not exists!", $facet));
			
			fs(".cache")->rm(sprintf("%s.json", strtolower($facet)));
		}

		$out->add(sprintf("Cache cleared%s.\n", notnull($facet)?sprintf(" [%s.json]", $facet):""));
	}
}