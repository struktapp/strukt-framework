<?php

namespace Strukt\Framework\Provider;

use Strukt\Router\RouteCollection;
use Strukt\Router\Route;
use Strukt\Event;
use Strukt\Contract\AbstractProvider;
use Strukt\Contract\ProviderInterface;
use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;

class Annotation extends AbstractProvider implements ProviderInterface{

	public function __construct(){

		$this->core()->set("app.annotations", new RouteCollection());
	}

	public function register(){

		$core = $this->core();

		$core->set("app.service.annotations", new Event(

			function($module_list) use($core){

			$annotations = [];

			foreach($module_list as $module){

				foreach($module["Router"] as $routr){

					/**
					* @todo either cache annotations or cache router loaded
					*		with annotations for speed and efficiency
					*/
					$class_name = sprintf("%s\Router\%s", $module["base-ns"], $routr);
					$parser = new BasicAnnotationParser(new \ReflectionClass($class_name));
					$annArr = $parser->getAnnotations();

					foreach($annArr as $annItem){

						foreach($annItem as $methodName=>$methodItems){

							if(array_key_exists("Method", $methodItems)){

								$name = "";
								if(array_key_exists("Permission", $methodItems))
									$name = $methodItems["Permission"]["item"];

								$annotations[] = array(

									"http_method" => $methodItems["Method"]["item"],
									"route" => $methodItems["Route"]["item"],
									"class" => $annArr["class_name"],
									"method" => $methodName,
									"name" => $name
								);
							}
						}
					}
				}
			}

			$core->get("app.annotations", $annotations);
		}));
	}
}