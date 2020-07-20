<?php

namespace Strukt;

/**
* Console Loader
*
* @author Moderator <pitsolu@gmail.com>
*/
class Console extends \Strukt\Console\Application{

	/**
	* Constructor loads Strukt Framework in-build applications
	*
	* Commands loaded:
	*
	* 	\Strukt\Console\Command\ApplicationGenerator
	* 	\Strukt\Console\Command\RouterGenerator
	* 	\Strukt\Console\Command\ModuleGenerator
	* 	\Strukt\Console\Command\ApplicationLoaderGenerator
	*/
	public function __construct(Array $config){

		$configKeys = array_keys($config);

		$registry = \Strukt\Core\Registry::getSingleton();

		if(in_array("moduleList", $configKeys))
			if(!is_null($config["moduleList"]))
				$registry->set("module-list", serialize($config["moduleList"]));

		if(!in_array("appName", $configKeys))
			$config["appName"] = "Strukt Console";

		if(!in_array("loadNativeCmds", $configKeys))
			$config["loadNativeCmds"] = true;

		if(!in_array("labelStruktSect", $configKeys))
			$config["labelStruktSect"] = false;

		parent::__construct($config["appName"]);

		if($config["labelStruktSect"])
			$this->addCmdSect("Strukt");

		if($config["loadNativeCmds"]){

			$this->add(new \Strukt\Console\Command\ApplicationGenerator);
			$this->add(new \Strukt\Console\Command\ApplicationLoaderGenerator);
			
			if($registry->exists("module-list")){

				$this->add(new \Strukt\Console\Command\RouterGenerator);
				$this->add(new \Strukt\Console\Command\ModuleGenerator);
				$this->add(new \Strukt\Console\Command\RouteList);
			}

			$this->add(new \Strukt\Console\Command\ShellExec);
			$this->add(new \Strukt\Console\Command\PackagePublisher);
		}
	}
}