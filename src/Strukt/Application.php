<?php

namespace Strukt;

use Strukt\Core\Map;
use Strukt\Core\Collection;
use Strukt\Core\Registry;
use Strukt\Fs;
use Strukt\Router\Router;
use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
* Strukt Application Module Loader and Runner
*
* @author Moderator <pitsolu@gmail.com>
*/
class Application{

	/**
	* Store resolved module items
	*
	* @var array
	*/
	private $modules;

	/**
	* Name Registry
	*
	* @var \Strukt\Core\Map
	*/
	private $nr;

	/**
	* Constructor
	*/
	public function __construct(){

		$this->modules = array();
		$this->nr = new Map(new Collection("NameRegistry"));
	}

	/**
	* Register Strukt Modules
	*
	* @param \App\Module $module
	*
	* @return void
	*/
	public function register(\App\Module $module){

		$ns = $module->getNamespace();
		$alias = $module->getAlias();
		$dir = $module->getBaseDir();

		list($appName, 
				$moduleName, 
				$fmoduleName) = explode("\\", $ns);

		$baseNs = trim(str_replace($fmoduleName, "", $ns),"\\");
		$this->modules[$fmoduleName]["base-ns"] = $baseNs;
		
		$_alias = strtolower($alias);
		$this->nr->set(sprintf("%s.app.name", $_alias), $appName);
		$this->nr->set(sprintf("%s.name", $_alias), $moduleName);
		$this->nr->set(sprintf("%s.fname", $_alias), $fmoduleName);
		$this->nr->set(sprintf("%s.ns", $_alias), $ns);
		$this->nr->set(sprintf("%s.base.ns", $_alias), $baseNs);
		$this->nr->set(sprintf("%s.dir", $_alias), $dir);

		$rootDir = Registry::getInstance()->get("_dir");

		if(!Fs::isPath($rootDir))
			throw new \Exception(sprintf("Root dir [%s] does not exist!", $rootDir));

		$modIniFile = sprintf("%s/cfg/module.ini", $rootDir);

		if(!Fs::isFile($modIniFile))
			throw new \Exception("Could not find [cfg/module.ini] file!");

		$modSettings = parse_ini_file($modIniFile);

		if(!in_array("folder", array_keys($modSettings)))
			throw new \Exception("Module Ini file [cfg/module.ini] must specify [alias=>folder] list!");

		foreach($modSettings["folder"] as $key=>$fldr){

			foreach(glob(sprintf("%s/%s/*", $dir, $fldr)) as $file){

				$fname = str_replace(".php", "", basename($file));

				$this->modules[$fmoduleName][$fldr][] = $fname;

				$this->nr->set(sprintf("%s.%s.%s", 
									strtolower($alias), 
									strtolower($key), 
									$fname),
								sprintf("%s\%s\%s",
									$baseNs,
									$fldr,
									$fname));
			}
		}	
	}

	/**
	* Getter for Name Registry 
	*
	* @return \Strukt\Core\Map
	*/
	public function getNameRegistry(){

		return $this->nr;
	}

	/**
	* Getter for Module Array
	*
	* @return Array
	*/
	public function getModuleList(){

		return $this->modules;
	}

	/**
	* Create router properties
	* 
	* @return array
	*/
	public function getRouterProperties(){

		/**
		* @todo either cache annotations or cache router loaded
		*		with annotations for speed and efficiency
		*/

		$registry = Registry::getInstance();

		$cache = null;
		$routerParams = null;
		if($registry->exists("cache")){

			$cache = $registry->get("cache");
			$routerParams = $cache->fetch("router-params");
		}

		if(empty($routerParams)){

			foreach($this->modules as $module){

				if(!empty($module["Router"]))
					foreach($module["Router"] as $routr){

						$rclass_name = sprintf("%s\Router\%s", $module["base-ns"], $routr);
						$rclass = new \ReflectionClass($rclass_name);
						$parser = new BasicAnnotationParser($rclass);
						$annotations = $parser->getAnnotations();

						foreach($annotations["methods"] as $name=>$items){

							$params = null;
							if(!empty($items)){
								
								$http_method = null;
								if(in_array("item", array_keys($items["Method"])))
									$http_method = $items["Method"]["item"];
								
								if(in_array("items", array_keys($items["Method"])))
									$http_method = implode("-", array_map("trim", $items["Method"]["items"]));
								
								if(is_null($http_method))
									throw new \Exception("StruktFrameworkApplicationException: Unrecorgnized method!");

								$perm = null;
								
								if(in_array("Permission", array_keys($items))){

									if(!is_null($items["Permission"])){

										if(in_array("items", array_keys($items["Permission"])))
											throw new \Exception("PermissionAnnotationException: Accepts only one permission per route!");	

										if(in_array("item", array_keys($items["Permission"])))
											$perm = $items["Permission"]["item"];
									}
								}

								$params["method"] = $http_method;
								$params["route"] = $items["Route"]["item"];
								$params["func"]["class"] = $rclass_name;
								$params["func"]["method"] = $name;
								$params["func"]["permission"] = $perm;

								$routerParams[] = $params;
							}
						}
					}
			}			
		}

		if(!is_null($cache))
			if(!$cache->exists("router-params"))
				$cache->save("router-params", $routerParams);
		
		$object = null;
		foreach($routerParams as $routerParam){

			if(get_class($object) != $routerParam["func"]["class"]){

				$class = new \ReflectionClass($routerParam["func"]["class"]);
				$object = $class->newInstance();
			}

			$func = $class->getMethod($routerParam["func"]["method"])->getClosure($object);

			$routerProps[$routerParam["func"]["class"]][] = array(

				"method"=>$routerParam["method"],
				"route"=>$routerParam["route"],
				"name"=>$routerParam["func"]["method"],
				"perm"=>$routerParam["func"]["permission"],
				"func"=>$func
			);
		}

		return $routerProps;
	}

	/**
	* Create router
	* 
	* @return Strukt\Rest\Router
	*/
	public function getRouter(){

		$registry = Registry::getInstance();

		$allowed = null;
		if($registry->exists("router.perms")){

			$allowed = $registry->get("router.perms");

			if(!is_array($allowed))
				$allowed = null;
		}

		$request = $registry->get("request");

		$router = new Router($allowed, $request);
		$routes = $router->getRoutes();

		$allRouterProps = $this->getRouterProperties();

		foreach($allRouterProps as $routerProp)
			foreach($routerProp as $prop)
				$routes->addRoute($prop["method"],
									$prop["route"],
									$prop["func"],
									$prop["perm"]);

		return $router;
	}

	/**
	* Execute router in debug mode
	*
	* @return void
	*/
	public function runDebug(){

		$res = $this->getRouter()->dispatch();

		$res->send();
	}

	/**
	* Execute router
	*
	* @return void
	*/
	public function run(){

		$registry = Registry::getInstance();

		try{

			$res = $this->getRouter()->dispatch();

			$res->send();
		}
		catch(\Exception $e){

			if($registry->exists("logger"))
				$registry->get("logger")->error($e);

			$res = $registry->get("Response.ServerError")->exec();

			exit($res->getContent());
		}
	}
}