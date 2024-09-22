<?php

helper("framework");

if(helper_add("repos")){

	function repos(string $type = null){

		if(is_null($type))
			return \Strukt\Package\Repos::available();

		return \Strukt\Package\Repos::packages($type);		
	}
}

if(helper_add("ddd")){

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

if(helper_add("form")){

	function form(string $which, \Strukt\Http\Request $request){

		$nr = reg()->get("nr");
		$aliases = $nr->get("modules")->keys();

		$forms = arr(array_flip($aliases))->each(function($a, $v) use($nr){

			$ls = arr($nr->get(str($a)->concat(".frm"))->keys())->each(function($k, $v) use($a){

				return str($a)->concat(".frm.")->concat($v)->yield();
			});

			$ls = arr(array_flip($ls->yield()))->each(function($k, $v){

				return str(arr(str($k)->split("."))->last()->yield())->toSnake()->yield();
			});

			return array_flip($ls->yield());
		});

		$forms = $forms->level();

		return core($forms[$which], [$request]);
	}
}

if(helper_add("validator")){

	function validator(string $type, mixed $value){

		$validator = new App\Validator($value);
		$validator = ref($validator)->method(lcfirst(str($type)->toCamel()->yield()))->invoke();
		$messages = $validator->getMessage();

		return reset($messages);
	}
}

if(helper_add("request")){

	function request(array $args = [], array $headers = null){

		$request = new \Strukt\Http\Request($args);
		if(notnull($headers))
			$request->headers->add($headers);

		return $request; 
	}
}

if(helper_add("core")){

	function core(string $alias, ?array $args = null){

		return event("provider.core")->apply($alias, $args)->exec();
	}
}