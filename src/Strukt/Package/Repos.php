<?php

namespace Strukt\Package;

use Strukt\Ref;

class Repos{

	public static function available(){

		$repos = config("repo");

		$packages = [];
		foreach($repos as $name => $repo)
			$packages[$name] = sprintf("Strukt\Package\%s", $repo);

		return $packages;
	}

	public static function packages($type){

		$packages = [];

		$type = str($type);

		arr(Repos::available())->each(function($name, $class) use(&$packages, $type){

			if($type->equals("installed")){

				if(class_exists($class))
					$packages[] = $name;
			}

			if($type->equals("published")){

				if(class_exists($class))
					if(Ref::create($class)->make()->getInstance()->isPublished())
						$packages[] = $name;
			}
		});

		return $packages;
	}
}