<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Fs;
use Strukt\Env;

/**
* sys:util  Enable/Disable CLI commands
* 
* Usage:
*	
*      sys:util <type> <facet> <name>
*
* Arguments:
*
*      type     options: (enable|disable)
*      facet    options: (middleware|provider|command)
*      name     options: middlewares: (auth|authz|except|sess|valid|cors)
*                         providers: (facet|valid|logger|strukt.asset)
*                         commands: (pkg-tests|pkg-roles|pkg-book|pkg-db|pkg-asset|pub-pak|pub-mak)
*/
class SysUtil extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$type = $in->get("type");
		$facet = $in->get("facet");
		$name = $in->get("name");

		$facet = str($facet);
		if($facet->equals("command"))
			$path = env("rel_cmd_ini");

		if($facet->equals("provider") || $facet->equals("middleware") )
			$path = env("rel_app_ini");

		if(in_array($facet, ["command"])){

			if(str($type)->equals("enable"))
				$output = ini($path)->enable($name)->yield();

			if(str($type)->equals("disable"))
				$output = ini($path)->disable($name)->yield();
		}

		if(in_array($facet, ["middleware", "provider"])){

			$facet = str($facet)->concat("s")->yield();//pluralize

			if(str($type)->equals("enable"))
				$output = ini($path)->enable($facet, $name)->yield();

			if(str($type)->equals("disable"))
				$output = ini($path)->disable($facet, $name)->yield();
	
			// $cfg = new \Strukt\Framework\Configuration();
			// $cfg->get("middlewares");
			// $cfg->get("providers");

			// $aliases = $cfg->getAliases();
			// if(!in_array($name, $aliases->get($facet)))
				// new \Strukt\Raise(sprintf("%s:%s does not exists!", $facet, $name));
		}

		//validate ini file
		$ok = false;
		if(@parse_ini_string($output) !== false)
			$ok = \Strukt\Fs::overwrite($path, $output);

		if(!$ok)
			raise("Something went wrong!");

		$out->add("Done.");
	}
}