<?php

if(!function_exists("repos")){

	function repos(string $type = null){

		if(is_null($type))
			return \Strukt\Package\Repos::available();

		return \Strukt\Package\Repos::packages($type);		
	}
}

if(!function_exists("ddd")){

	// ini_set("xdebug.var_display_max_depth", 10);

	function ddd(mixed $message){

		if(php_sapi_name() == "cli"){

			if(is_array($message))
				$message = json($message)->pp();

			if(!is_array($message) && !is_string($message)){

				ob_start();
				var_export($message);
				$message = ob_get_contents();
				ob_end_clean();
			}

			if(is_string($message))
				print_r(color("yellow", $message));
		}
	}
}