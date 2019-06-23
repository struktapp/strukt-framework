<?php

namespace Strukt\Framework\Middleware;

use Strukt\Router\Middleware\Middleware;
use Strukt\Http\Request;
use Strukt\Http\Response;
use Strukt\Core\Registry;

use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;


class Annotations extends Middleware{

	private $annoTypes;

	public function __construct(array $annoTypes){

		$this->annoTypes = $annoTypes;
	}

	public function __invoke(Request $request, Response $response, callable $next){

		// print_r(Registry::getInstance());exit;

		// $registry = Registry::getInstance();

		foreach(glob("app/src/**/**/Router/**") as $file){                                                                                          $class = str_replace(array("app/src/",".php", "/"), array("", "", "\\"), $file);

			$parser = new BasicAnnotationParser(new \ReflectionClass($class));
			$facets = $parser->getAnnotations();

			foreach($facets["methods"] as $method=>$property){

				foreach($this->annoTypes as $type){

					if(array_key_exists($type, $property)){

						$annotations[$method][$property[$type]["name"]] = $property[$type]["item"];
					}

				}
			}

			// print_r($annotations);
		}



		// $registry->

		// foreach($this->modules as $module){

		// 	foreach($module["Router"] as $routr){

		// 		$rclass_name = sprintf("%s\Router\%s", $module["base-ns"], $routr);
		// 		$rclass = new \ReflectionClass($rclass_name);
		// 		

		// 		foreach($annotations as $annotation){

		// 			foreach($annotation as $methodName=>$methodItems){

		// 				if(array_key_exists("Method", $methodItems)){

		// 					$class = sprintf("%s@%s", $annotations["class_name"], $methodName);

		// 					$this->router->map($methodItems["Method"]["item"],
		// 										$methodItems["Route"]["item"],
		// 										$class);
		// 				}
		// 			}
		// 		}
		// 	}
		// }

		return $next($request, $response);
	}
}

