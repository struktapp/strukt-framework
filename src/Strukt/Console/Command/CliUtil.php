<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Fs;
use Strukt\Env;

/**
* cli:util  Enable/Disable optional CLI commands
* 
* Usage:
*	
*      cli:util <type> <command>
*
* Arguments:
*
*      type        Options: enable, disable
*      command     Options: pub-pak, pub-make, pkg-tests etc
*/
class CliUtil extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$cmd = $in->get("command");
		$type = $in->get("type");

		$filename = Env::get("rel_cmd_ini");
		$ini = \Strukt\Fs::cat($filename);

		if($type == "enable"){

			$pattern = sprintf('/;(\s)%s/', $cmd);
			$replace = $cmd;
		}

		if($type == "disable"){

			$pattern = sprintf('/%s/', $cmd);
			$replace = sprintf('; %s', $cmd);
		}

		$output = preg_replace($pattern, $replace, $ini);

		\Strukt\Fs::overwrite($filename, $output);

		$out->add("Done.");
	}
}