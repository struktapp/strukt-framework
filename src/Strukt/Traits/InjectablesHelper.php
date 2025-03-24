<?php

namespace Strukt\Traits;

use Strukt\Framework\Injectable\Facet as InjectableFacet;

/**
* @author Moderator <pitsolu@gmail.com>
*/
trait InjectablesHelper{

	/**
	 * @param string $class
	 * 
	 * @return array|null
	 */
	public static function resolveInjectables(string $class):array|null{

		$injectable = new InjectableFacet(new \ReflectionClass($class));
		$configs = $injectable->getConfigs();	

		// dd($configs);
		// print_r([$class, $configs]);

		if(notnull($configs)){
			
			$configs = collect($configs);
			$configs = [

				"class"=>$configs->get("class"),
				"alias"=>$configs->get("config.name"),
				"required"=>$configs->get("config.is_required")
			];
		}

		return 	$configs;
	}
}