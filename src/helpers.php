<?php

if(!function_exists("config")){

	function config(string $key, array|string $options = null){

		if(!reg()->exists("config")){

			$ini_files = glob("cfg/*.ini");
			foreach($ini_files as $ini_file)
				reg(sprintf("config.%s", str($ini_file)
											->replace(["cfg/",".ini"],"")
											->yield()), parse_ini_file($ini_file));

			$app_config = reg("config.app");
			$app_name = $app_config["app-name"];
			unset($app_config["app-name"]);
			$app_config["name"] = $app_name;
			reg("config")->remove("app");
			reg("config")->set("app", collect($app_config));
		}

		$nkey = sprintf("config.%s", rtrim($key, "*"));
		if(str($key)->endsWith("*"))
			return arr(array_flip(reg($nkey)->getKeys()))->each(function($k, $v) use($nkey){

				return reg($nkey)->get($k);

			})->yield();


		if(reg("config")->exists($key))
			return reg("config")->get($key);

		return null;
	}
}