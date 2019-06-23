<?php

namespace Strukt;

use Strukt\Core\Map;
use Strukt\Core\Collection;
use Strukt\Core\Registry;
use Strukt\Fs;
use Strukt\Router\Kernel as RouterKernel;
use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;

use Strukt\Http\Response;
use Strukt\Http\Request;

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
	public function __construct(RouterKernel $router = null){

		$this->router = $router;
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

		$rootDir = Env::get("root_dir");
		$relModIni = Env::get("rel_mod_ini");

		if(!Fs::isPath($rootDir))
			throw new \Exception(sprintf("Root dir [%s] does not exist!", $rootDir));

		$modIniFile = sprintf("%s/%s", $rootDir, $relModIni);

		if(!Fs::isFile($modIniFile))
			throw new \Exception(sprintf("Could not find [%s] file!", $relModIni));

		$modSettings = parse_ini_file($modIniFile);

		if(!in_array("folder", array_keys($modSettings)))
			throw new \Exception(sprintf("Module Ini file [%s] must specify [alias=>folder] list!", $relModIni));

		foreach($modSettings["folder"] as $key=>$fldr){

    		$it = new \DirectoryIterator(sprintf("%s/%s/", $dir, $fldr));

    		foreach($it as $file){

				if($it->isFile()){

					$fname = str_replace(".php", "", $it->getFilename());

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

		// print_r($this->modules);
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
	private function loadRouter(){

		if(is_null($this->router))
			throw new \Exception("%s is required by %s!", RouterKernel::class, get_class($this));

		/**
		* @todo either cache annotations or cache router loaded
		*		with annotations for speed and efficiency
		*/

		foreach($this->modules as $module){

			foreach($module["Router"] as $routr){

				$rclass_name = sprintf("%s\Router\%s", $module["base-ns"], $routr);
				$rclass = new \ReflectionClass($rclass_name);
				$parser = new BasicAnnotationParser($rclass);
				$annotations = $parser->getAnnotations();

				foreach($annotations as $annotation){

					foreach($annotation as $methodName=>$methodItems){

						if(array_key_exists("Method", $methodItems)){

							$class = sprintf("%s@%s", $annotations["class_name"], $methodName);

							$this->router->map($methodItems["Method"]["item"],
												$methodItems["Route"]["item"],
												$class);
						}
					}
				}
			}
		}
	}

	/**
	* Execute router in debug mode
	*
	* @return void
	*/
	public function runDebug(){

		$this->loadRouter();

		$response = $this->router->run();

		exit($response->getContent());
	}

	/**
	* Execute router
	*
	* @return void
	*/
	public function run(){

		$registry = Registry::getInstance();

		try{

			$this->loadRouter();

			$response = $this->router->run();

			exit($response->getContent());
		}
		catch(\Exception $e){

			if($registry->exists("logger"))
				$registry->get("logger")->error($e);

			exit($e->getMessage());
		}
	}
}