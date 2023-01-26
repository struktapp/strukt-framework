<?php

namespace Strukt\Framework\Provider;

use Strukt\Router\RouteCollection;
use Strukt\Router\Route;
use Strukt\Event;
use Strukt\Contract\Provider\AbstractProvider;
use Strukt\Contract\Provider\ProviderInterface;
// use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;
use Strukt\Framework\Service\Router\Injectable as InjectableRouter;

class Router extends AbstractProvider implements ProviderInterface{

	public function __construct(){

		$this->core()->set("strukt.router", new RouteCollection());
	}

	public function register(){

		$core = $this->core();

		$core->set("strukt.service.router", new Event(function($module_list) use($core){

				/**
				* @todo either cache annotations or cache router loaded
				*		with annotations for speed and efficiency
				*/
				foreach($module_list as $module){

					foreach($module["Router"] as $router){

						$class = sprintf("%s\Router\%s", $module["base-ns"], $router);

						// print_r($class);

						$rClass = new \ReflectionClass($class);

						$rInj = new InjectableRouter($rClass);
						$rInjLs = $rInj->getConfigs();

						// print_r($rInjLs);

						foreach($rInjLs as $rItm){

							// print_r($rItm);

							$rMethod = $rClass->getMethod($rItm["ref.method"]);
							$rFunc = $rMethod->getClosure($rClass->newInstance());

							$route = new Route($rItm["route.path"], 
												$rFunc,
												$rItm["http.method"], 
												$rItm["route.perm"]);

							$core->get("strukt.router")->addRoute($route);
						}

						
						// $class_name = sprintf("%s\Router\%s", $module["base-ns"], $router);
						// $parser = new BasicAnnotationParser(new \ReflectionClass($class_name));
						// $annotations = $parser->getAnnotations();

						// foreach($annotations as $annotation){

						// 	foreach($annotation as $methodName=>$methodItems){

						// 		if(array_key_exists("Method", $methodItems)){

						// 			$http_method = $methodItems["Method"]["item"];
						// 			$pattern = $methodItems["Route"]["item"];
						// 			$class = $annotations["class_name"];

						// 			$name = "";
						// 			if(array_key_exists("Permission", $methodItems))
						// 				$name = $methodItems["Permission"]["item"];

						// 			if(empty($name))
						// 				if(array_key_exists("Auth", $methodItems))
						// 					$name = "strukt:auth";

						// 			$rClass = new \ReflectionClass($class);
						// 			$rMethod = $rClass->getMethod($methodName);
		 			// 				$route_func = $rMethod->getClosure($rClass->newInstance());

						// 			$route = new Route($pattern, 
						// 								$route_func, 
						// 								$http_method, 
						// 								$name);

						// 			$core->get("strukt.router")->addRoute($route);
						// 		}
						// 	}
						// }
					}
				}
			}
		));
	}
}