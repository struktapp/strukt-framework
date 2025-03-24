<?php

namespace Strukt\Package;

use Strukt\Ref;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class Repos{

	/**
	 * @return array 
	 */
	public static function available():array{

		$repos = config("repo*");

		$packages = [];
		foreach($repos as $name => $repo)
			$packages[$name] = sprintf(str($repo)->equals("Extender")?"App\%s":"Strukt\Package\%s", $repo);

		return $packages;
	}

	/**
	 * @param string $type
	 * 
	 * @return array
	 */
	public static function packages(string $type):array{

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