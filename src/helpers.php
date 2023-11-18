<?php

if(!function_exists("config")){

	function config(string $key, array|string $options = null){

		if(!reg()->exists("config")){

			$cfg = fs(".cache/cfg");
			if($cfg->isFile("cfg.php")){

				$cfg_ls = $cfg->req("cfg.php");
				reg("config", $cfg_ls);
			}

			if(!$cfg->isFile("cfg.php")){

				$cfg_dir = fs("cfg");
				$ini_files = $cfg_dir->ls();
				foreach($ini_files as $ini_file){

					$facet = str($ini_file)->replace(".ini","")->yield();
					$configs = $cfg_dir->ini($ini_file);
					foreach($configs as $name=>$val)
						reg(sprintf("config.%s.%s", $facet, $name), $val);
				}

				if(reg("config")->exists("app")){
					
					$app_config = reg("config.app");
					$app_name = $app_config->get("app-name");
					$app_config->remove("app-name");
					$app_config->set("name", $app_name);
				}
			}
		}

		$nkey = sprintf("config.%s", rtrim($key, "*"));
		if(str($key)->endsWith("*"))
			return arr(array_flip(reg($nkey)->getKeys()))->each(function($k, $v) use($nkey){

				return reg($nkey)->get($k);

			})->yield();

		if(!is_null($options))
			reg(sprintf("config.%s", $key), $options);

		if(reg("config")->exists($key))
			return reg("config")->get($key);

		return null;
	}
}

// if(!function)

if(!function_exists("undot")){

	function undot(array $list){

		// $app_name = $list["app"]["app-name"];
		// unset($list["app"]["app-name"]);
		// $list["app"]["name"] = $app_name;
		arr($list)->recur(function($k, $v) use(&$d){

			if(str_contains($k, ".")){

				list($a, $b, $c) = explode(".", $k);

				if(!empty($c))
					$d[$a][$b] = $v;

				if(empty($c))
					$d[$a][$b][$c] = $v;				
			}

			$d[$k] = $v;
		});

		return $d;
	}
}