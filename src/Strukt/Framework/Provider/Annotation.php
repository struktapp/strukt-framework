<?php

namespace Strukt\Framework\Provider;

use Strukt\Router\RouteCollection;
// use Strukt\Router\Route;
use Strukt\Event;
use Strukt\Contract\Provider\AbstractProvider;
use Strukt\Contract\Provider\ProviderInterface;
// use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;
use Strukt\Framework\Service\Router\Injectable as InjectableRouter;

class Annotation extends AbstractProvider implements ProviderInterface{

	public function __construct(){

		$this->core()->set("strukt.annotations", new RouteCollection());
		// $this->core()->set("strukt.annotations", []);
	}

	public function register(){

		$core = $this->core();

		$core->set("strukt.service.annotations", new Event(

			function($module_list) use($core){

			$annotations = [];

			foreach($module_list as $module){

				foreach($module["Router"] as $router){

					/**
					* @todo either cache annotations or cache router loaded
					*		with annotations for speed and efficiency
					*/
					$class = sprintf("%s\Router\%s", $module["base-ns"], $router);

						// print_r($class);

					$rClass = new \ReflectionClass($class);

					$rInj = new InjectableRouter($rClass);
					$annotations = $rInj->getConfigs();

					// $class_name = sprintf("%s\Router\%s", $module["base-ns"], $routr);
					// $parser = new BasicAnnotationParser(new \ReflectionClass($class_name));
					// $annArr = $parser->getAnnotations();

					// foreach($annArr as $annItem){

					// 	foreach($annItem as $methodName=>$methodItems){

					// 		if(array_key_exists("Method", $methodItems)){

					// 			$name = "";
					// 			if(array_key_exists("Permission", $methodItems))
					// 				$name = $methodItems["Permission"]["item"];

					// 			$annotations[] = array(

					// 				"http_method" => $methodItems["Method"]["item"],
					// 				"route" => $methodItems["Route"]["item"],
					// 				"class" => $annArr["class_name"],
					// 				"method" => $methodName,
					// 				"name" => $name
					// 			);
					// 		}
					// 	}
					// }
				}
			}

			$core->get("strukt.annotations", $annotations);
		}));
	}
}