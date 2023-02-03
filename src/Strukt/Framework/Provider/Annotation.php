<?php

namespace Strukt\Framework\Provider;

use Strukt\Router\RouteCollection;
use Strukt\Event;
use Strukt\Contract\Provider\AbstractProvider;
use Strukt\Contract\Provider\ProviderInterface;
use Strukt\Framework\Service\Router\Injectable as InjectableRouter;
/**
* @Name(strukt.annotations)
* @Require(must)
*/
class Annotation extends AbstractProvider implements ProviderInterface{

	public function __construct(){

		$this->core()->set("strukt.annotations", new RouteCollection());
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

					$rClass = new \ReflectionClass($class);

					$rInj = new InjectableRouter($rClass);
					$annotations = $rInj->getConfigs();
				}
			}

			$core->get("strukt.annotations", $annotations);
		}));
	}
}