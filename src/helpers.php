<?php

if(!function_exists("config")){

	function config(string $key, array|string $options = null){

		$cache_cfg = false;
		if(!reg()->exists("config")){

			$cfg = fs(".cache/cfg");
			if($cfg->isFile("cfg.php")){

				$cache_cfg = true;
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

if(!function_exists("repos")){

	function repos(string $type = null){

		if(is_null($type))
			return \Strukt\Package\Repos::available();

		return \Strukt\Package\Repos::packages($type);		
	}
}