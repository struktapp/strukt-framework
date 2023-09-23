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
*                         providers: (logger|nmlz|sch-mgr|ent-mgr|doc-adp)
*                         commands: (pub-pak|pub-mak|pkg-tests|pkg-do|pkg-roles)
*/
class SysUtil extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$type = $in->get("type");
		$facet = $in->get("facet");
		$name = $in->get("name");

		if(in_array($facet, ["command"])){

			$filename = Env::get("rel_cmd_ini");

			if($type == "enable"){

				$pattern = sprintf('/;(\s)%s/', $name);
				$replace = $name;
			}

			if($type == "disable"){

				$pattern = sprintf('/%s/', $name);
				$replace = sprintf('; %s', $name);
			}
		}

		if(in_array($facet, ["middleware", "provider"])){

			$filename = Env::get("rel_app_ini");

			if($type == "enable"){

				$pattern = sprintf('/;(\s)%ss(.*)%s/', $facet, $name);
				$replace = sprintf("%ss[] = %s", $facet, $name);
			}

			if($type == "disable"){

				$pattern = sprintf('/%ss(.*)%s/', $facet, $name);
				$replace = sprintf("; %ss[] = %s", $facet, $name);
			}

			$cfg = new \Strukt\Framework\Configuration();
			$cfg->get("middlewares");
			$cfg->get("providers");

			$aliases = $cfg->getAliases();
			if(!in_array($name, $aliases[sprintf("%ss", $facet)]))
				new \Strukt\Raise(sprintf("%s:%s does not exists!", $facet, $name));
		}

		$ini = \Strukt\Fs::cat($filename);

		$output = preg_replace($pattern, $replace, $ini);

		\Strukt\Fs::overwrite($filename, $output);

		$out->add("Done.");
	}
}