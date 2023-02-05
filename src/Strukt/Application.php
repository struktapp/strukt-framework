<?php

namespace Strukt;

use Strukt\Core\Map;
use Strukt\Core\Collection;
use Strukt\Router\Kernel as RouterKernel;
// use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;
use Strukt\Contract\Module;
use Strukt\Env;
use Strukt\Raise;
use Strukt\Fs;
use Strukt\Contract\AbstractCore;
use Strukt\Http\Response\Plain as Response;
use Strukt\Http\Request;
use Strukt\Framework\Module\Core;

/**
* Strukt Application Module Loader and Runner
*
* @author Moderator <pitsolu@gmail.com>
*/
class Application extends AbstractCore{

	/**
	* Router Kernel
	*
	* @var \Strukt\Router\Kernel
	*/
	private $router;

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
	* Module Configuration
	*
	* @var array
	*/
	private $mod_cfg;



	/**
	* Constructor
	*/
	public function __construct(RouterKernel $router = null){

		$this->router = $router;
		$this->modules = array();
		$this->nr = new Map(new Collection("NameRegistry"));

		$root_dir = Env::get("root_dir");
		$mod_ini = Env::get("rel_mod_ini");
		$app_ini = Env::get("rel_app_ini");

		Fs::isPath($root_dir) or new Raise(sprintf("Root dir [%s] does not exist!", $root_dir));

		$app_ini_file = sprintf("%s/%s", $root_dir, $app_ini);

		Fs::isFile($app_ini_file) or new Raise(sprintf("Could not find [%s] file!", $app_ini));

		$app_info = parse_ini_file($app_ini_file);

		$this->core()->set("app.name", $app_info["app-name"]);

		$mod_ini_file = sprintf("%s/%s", $root_dir, $mod_ini);

		Fs::isFile($mod_ini_file) or new Raise(sprintf("Could not find [%s] file!", $mod_ini));

		$this->mod_cfg = parse_ini_file($mod_ini_file);

		if(!in_array("folder", array_keys($this->mod_cfg)))
			new Raise(sprintf("Module Ini file [%s] must specify [alias=>folder] list!", $mod_ini));
	}

	/**
	* Register Strukt Modules
	*
	* @param \App\Module $module
	*
	* @return void
	*/
	public function register(Module $module){

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

		foreach($this->mod_cfg["folder"] as $key=>$fldr){

			$facet_dir = sprintf("%s/%s/", $dir, $fldr);

			$isPath = Fs::isPath($facet_dir);

			if($isPath){

	    		$it = new \DirectoryIterator($facet_dir);

	    		foreach($it as $file){

					if($it->isFile()){

						if(!preg_match("/\w+\~$/", $it->getFilename())){

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
			}
		}	
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
	* Execute router
	*
	* @return void
	*/
	public function run(){

		$core = $this->core();
		$modList = $this->getModuleList();
		$nr = $this->nr;
		$router = $this->router;

		return new class($core, $router, $modList, $nr){

			private $core, $router, $modList, $nr, $isInit;

			public function __construct($core, $router, $modList, $nr){

				$this->core = $core;
				$this->router = $router;
				$this->modList = $modList;
				$this->nr = $nr;
				$this->isInit = false;
			}

			/**
			* Create router properties
			* 
			* @return array
			*/
			public function init(){

				$this->core->set("nr", $this->nr);
				$this->core->set("core", new Core);

				if($this->core->exists("strukt.service.router"))
					$this->core->get("strukt.service.router")
									->apply($this->modList)
									->exec();

				$this->isInit = true;

				return $this;
			}

			public function debug(){

				try{

					if(!$this->isInit)
						$this->init();

					$response = $this->router->run();
					$response->sendHeaders();

					exit($response->getContent());
				}
				catch(\Exception $e){

					if($this->core->exists("strukt.logger"))
						$this->core->get("strukt.logger")->error($e);

					exit($e->getMessage());
				}
			}

			public function noDebug(){

				if(!$this->isInit)
					new Raise("Strukt\Application::run@anonymous::init must be called first!");

				$response = $this->router->run();
				$response->sendHeaders();

				exit($response->getContent());
			}
		};
	}
}