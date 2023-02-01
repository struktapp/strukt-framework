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
				$forms = [];

				foreach($module_list as $module){

					foreach($module["Router"] as $router){

						$class = sprintf("%s\Router\%s", $module["base-ns"], $router);

						$rClass = new \ReflectionClass($class);

						$rInj = new InjectableRouter($rClass);
						$rInjLs = $rInj->getConfigs();

						foreach($rInjLs as $rItm){

							$rMethod = $rClass->getMethod($rItm["ref.method"]);
							$rFunc = $rMethod->getClosure($rClass->newInstance());

							$form = "";
							if(!empty($rItm["route.form"])){

								$refClsLs = explode("\\", $rItm["ref.class"]);
								$appName = array_shift($refClsLs);
								$modName = array_shift($refClsLs);

								$form = sprintf("%s\%s\Form\%s", 
													$appName, 
													$modName,
													$rItm["route.form"]);
							}

							$tokens = [];
							if(!empty($form))
								$tokens[] = sprintf("@forms|%s:%s", $rItm["http.method"], $form);

							$route = new Route($rItm["route.path"], 
												$rFunc,
												$rItm["http.method"], 
												$rItm["route.perm"],
												$tokens);

							$core->get("strukt.router")->addRoute($route);
						}
					}
				}
			}
		));
	}
}