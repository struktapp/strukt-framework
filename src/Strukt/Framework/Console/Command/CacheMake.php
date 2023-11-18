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

		$fsRoot = fs();
		if(!$fsRoot->isDir(".cache/cfg"))
			$fsRoot->mkdir(".cache/cfg");
		
		$fsCfg = fs(".cache/cfg");
		$fsCfg->rm("cfg.php");
		$fsCfg->touchWrite("cfg.php", "<?php\n return ");
		$fsCfg->appendWrite("cfg.php", str(var_export($cache->yield(), true))->concat(";")->yield());

		$tpl_ls = fs()->lsr(".tpl/sgf/app/");
		if(!$fsRoot->isDir(".cache/files"))
			$fsRoot->mkdir(".cache/files");

		$fsFiles = fs(".cache/files");
		$fsFiles->rm("tpl_ls.php");
		$fsFiles->touchWrite("tpl_ls.php", "<?php\n return ");
		$fsFiles->appendWrite("tpl_ls.php", str(var_export($tpl_ls, true))->concat(";")->yield());

		$tpl_app = arr(array_flip(fs()->lsr(".tpl/sgf/app")))->each(fn($k, $v)=>fs()->cat($k))->yield();
		$fsFiles->rm("tpl_app.php");
		$fsFiles->touchWrite("tpl_app.php", "<?php\n return ");
		$fsFiles->appendWrite("tpl_app.php", str(var_export($tpl_app, true))->concat(";")->yield());
	}
}