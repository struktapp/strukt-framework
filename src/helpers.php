<?php

if(!function_exists("fs")){

	function fs(string $base_path = null){

		if(!is_null($base_path))
			return new \Strukt\Local\Fs($base_path);

		return \Strukt\Ref::create(\Strukt\Fs::class)->noMake()->getInstance();
	}
}

if(!function_exists("env")){

	function env(){

		return \Strukt\Ref::create(\Strukt\Env::class)->noMake()->getInstance();
	}
}