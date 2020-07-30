<?php

namespace Strukt\Framework\Provider;

use Strukt\Router\RouteCollection;
use Strukt\Router\Route;
use Strukt\Event;
use Strukt\Contract\AbstractProvider;
use Strukt\Contract\ProviderInterface;
use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;

class Router extends AbstractProvider implements ProviderInterface{

	public function __construct(){

		$this->core()->set("app.router", new RouteCollection());
	}

	public function register(){

		$this->core()->set("app.service.router", new Event(function($module_list){

			// print_r($module_list);

				foreach($module_list as $module){

					foreach($module["Router"] as $router){

						/**
						* @todo either cache annotations or cache router loaded
						*		with annotations for speed and efficiency
						*/
						$class_name = sprintf("%s\Router\%s", $module["base-ns"], $router);
						$parser = new BasicAnnotationParser(new \ReflectionClass($class_name));
						$annotations = $parser->getAnnotations();

						// print_r($annotations);

						foreach($annotations as $annotation){

							foreach($annotation as $methodName=>$methodItems){

								// print_r(array($methodName, $methodItems));

								if(array_key_exists("Method", $methodItems)){

									$http_method = $methodItems["Method"]["item"];
									$pattern = $methodItems["Route"]["item"];
									$class = $annotations["class_name"];

									$name = "";
									if(array_key_exists("Permission", $methodItems))
										$name = $methodItems["Permission"]["item"];

									$rClass = new \ReflectionClass($class);
		 							$route_func = $rClass->getMethod($methodName)
		 													->getClosure($rClass->newInstance());

		 							// print_r(array($pattern, $route_func, $http_method, $name));

									$route = new Route($pattern, $route_func, $http_method, $name);

									$this->core()->get("app.router")->addRoute($route);
								}
							}
						}
					}
				}
			}
		));
	}
}