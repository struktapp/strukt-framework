<?php

namespace Strukt\Framework;

use Strukt\Router\Kernel as RouterKernel;
use Strukt\Framework\Contract\Module as ModuleInterface;
use Strukt\Framework\Injectable\Router as InjectableRouter;
use Strukt\Fs;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class Application{

	private $router;
	private $aliases;
	private $modules;

	/**
	 * @param \Strukt\Router\Kernel $router
	 */
	public function __construct(RouterKernel $router){

		$this->router = $router;
		$this->aliases = [];
		$this->modules = [];

		// Name Registry
		reg("nr", collect([]));
	}

	/**
	 * Register app facets(router, form, controller, tests, etc)  in name registry (nr)
	 * 
	 * @param \Strukt\Framework\Contract\Module $module
	 * 
	 * @return void
	 */
	public function register(ModuleInterface $module):void{

		$ns = $module->getNamespace();
		$alias = $module->getAlias();
		$dir = $module->getBaseDir();

		if(notnull($this->modules))
			if(in_array(get_class($module), $this->modules))
				return;

		$this->modules[str($alias)->toLower()->yield()] = $ns;

		list($app_name, 
				$module_name, 
				$module_class) = explode("\\", $ns);

		$base_ns = str($app_name)->concat("\\")->concat($module_name)->yield();
		$facets = array_flip(config("module.folders*"));
		$alias = str($alias)->toLower()->yield();

		$route_nss = arr(Fs::lsr($dir))->each(function($k, $v) use($dir, $base_ns, $facets, $alias, $module_class){

			$base_path = trim(str($v)->replace([$dir,".php"], "")->yield(),"/");
			if(!str($base_path)->equals($module_class)){

				list($facet, $class) = str($base_path)->split("/");

				$base_alias = null;
				if(array_key_exists($facet, $facets))
					$base_alias = sprintf("%s.%s.%s", $alias, $facets[$facet], $class);

				$facet_ns = str($base_path)
								->replace("/","\\")
								->prepend(str($base_ns)
											->concat("\\")
											->yield())->yield();
				
				if(negate(reg("nr")->exists($base_alias)))
					if(notnull($base_alias))
						reg(sprintf("nr.%s", $base_alias), $facet_ns);

				if(str($facet)->equals("Router"))
					return $facet_ns;

				return null;
			}
		});

		$this->aliases[] = $alias;
		reg(sprintf("nr.routes.%s", $alias), array_filter($route_nss->yield(), function($v){

			return !is_null($v);
		}));
	}

	/**
	 * Create configs, cache and router
	 * 
	 * @return static
	 */
	public function init():static{

		reg("nr.modules", $this->modules);

		$configs = [];
		if(cache("rtr")->empty()){

			arr($this->aliases)->each(function($key, $alias) use(&$configs){

				arr(reg(sprintf("nr.routes.%s", $alias)))->each(function($key, $class) use(&$configs, $alias){

					$inj_rtr = new InjectableRouter(new \ReflectionClass($class));
					arr($inj_rtr->getConfigs())->each(function($key, $config) use(&$configs, $alias){

						$configs[] = array(

							"action"=>$config["http.method"],
							"route"=>$config["route.path"],
							"class"=>$config["ref.class"],
							"callable"=>$config["ref.method"],
							"permissions"=>$config["route.perm"],
							"form"=>$config["route.form"],
							"middlewares"=>$config["route.middlewares"],
							"module"=>$alias
						);
					});
				});
			});

			cache("rtr")->put("configs", $configs)->save();
		}

		if(empty($configs))
			$configs = cache("rtr")->get("configs");

		foreach($configs as $config){

			$other_configs = [];
			$callable = \Strukt\Ref::create($config["class"])
									->noMake()
									->method($config["callable"])
									->getClosure();

			$other_configs["module"] = $config["module"];
			if(!empty($config["permissions"]))
				$other_configs["allows"] = $config["permissions"];

			if(!empty($config["middlewares"]))
				$other_configs["middlewares"] = $config["middlewares"];

			if(!empty($config["form"]))
				$other_configs["form"] = $config["form"];

			$this->router->add(path:$config["route"], 
									func:$callable,
									action:$config["action"],
									config:arr($other_configs)->tokenize());
		}

		return $this;
	}

	public function run(){

		$this->init();
		return $this->router->run();
	}
}