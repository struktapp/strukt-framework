<?php

namespace Strukt\Package;

use Strukt\Env;
use Strukt\Ref;
use Strukt\Raise;
use Strukt\Type\Str; 
use Strukt\Type\Arr;

class Repos{

	public static function available(){

		$repos = parse_ini_file(Env::get("rel_repo_ini"));

		foreach($repos as $name => $repo)
			$packages[$name] = sprintf("Strukt\Package\%s", $repo);

		return $packages;
	}

	public static function packages($type){

		$pkgs = [];

		$type = Str::create($type);

		Arr::create(static::available())->each(function($name, $cls) use(&$pkgs, $type){

			if($type->equals("installed")){

				if(class_exists($cls))
					$pkgs[] = $name;
			}

			if($type->equals("published")){

				if(class_exists($cls))
					if(Ref::create($cls)->make()->getInstance()->isPublished())
						$pkgs[] = $name;
			}
		});

		return $pkgs;
	}
}