<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* cache:make     Create cache
*
* Usage:
*
*      cache:make
*/
class CacheMake extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$fsRoot = fs();
		$ls = $fsRoot->ls("cfg");

		$cache = [];
		foreach($ls as $file)
			$cache[trim($file, ".ini")] = fs("cfg")->ini($file);

		$cache = arr($cache)->each(function($k, $v){

			$k = str($k);
			if($k->equals("app")){

				$v["name"] = $v["app-name"];
				unset($v["app-name"]);

				return $v;
			}

			if($k->equals("cmd")){

				$d = [];
				foreach($v as $kk=>$vv){

					list($pkg, $sect) = explode(".", $kk);
					$d[$pkg][$sect] = $v[$kk];
				}

				return $d;
			}

			return $v;
		});

		$fsCfg = fs(".cache/cfg");
		$fsCfg->rm("cfg.php");
		$fsCfg->touchWrite("cfg.php", "<?php\n return ");
		$fsCfg->appendWrite("cfg.php", str(var_export($cache->yield(), true))->concat(";")->yield());
	}
}