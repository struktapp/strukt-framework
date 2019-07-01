<?php

namespace Strukt;

use Strukt\Core\Map;
use Strukt\Core\Collection;
use Strukt\Router\Kernel as RouterKernel;
use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;
use Strukt\Contract\Module;
use Strukt\Env;
use Strukt\Raise;
use Strukt\Fs;
use Strukt\Contract\AbstractCore;
use Strukt\Http\Response;
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

		$rootDir = Env::get("root_dir");
		$relModIni = Env::get("rel_mod_ini");

		Fs::isPath($rootDir) or new Raise(sprintf("Root dir [%s] does not exist!", $rootDir));

		$modIniFile = sprintf("%s/%s", $rootDir, $relModIni);

		Fs::isFile($modIniFile) or new Raise(sprintf("Could not find [%s] file!", $relModIni));

		$modSettings = parse_ini_file($modIniFile);

		in_array("folder", array_keys($modSettings)) or new Raise(sprintf("Module Ini file [%s] must specify [alias=>folder] list!", $relModIni));

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
	public function initialize(){

		$core = $this->core();

		$core->set("nr", $this->nr);
		$core->set("core", new Core);

		// if(is_null($this->router))
			// throw new \Exception("%s is required by %s!", RouterKernel::class, get_class($this));

		if($core->exists("app.service.annotations"))
			$core->get("app.service.annotations")
							->apply($this->getModuleList())
							->exec();

		if($core->exists("app.service.router"))
			$core->get("app.service.router")
							->apply($this->getModuleList())
							->exec();
	}

	/**
	* Execute router in debug mode
	*
	* @return void
	*/
	public function runDebug(){

		$this->initialize();

		$response = $this->router->run();

		exit($response->getContent());
	}

	/**
	* Execute router
	*
	* @return void
	*/
	public function run(){

		try{

			$this->initialize();

			$response = $this->router->run();

			exit($response->getContent());
		}
		catch(\Exception $e){

			if($this->core()->exists("app.logger"))
				$this->core()->get("app.logger")->error($e);

			exit($e->getMessage());
		}
	}
}