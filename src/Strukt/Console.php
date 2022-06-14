<?php

namespace Strukt;

use Strukt\Env;
use Strukt\Core\Registry;

/**
* Console Loader
*
* @author Moderator <pitsolu@gmail.com>
*/
class Console extends \Strukt\Console\Application{

	/**
	* Constructor loads Strukt Framework in-build applications
	*/
	public function __construct($load_native_cmds = true){

		$registry = Registry::getSingleton();

		parent::__construct(Env::get("cli_app_name"));

		$this->addCmdSect(Env::get("cli_label"));

		if($load_native_cmds){

			$this->add(new \Strukt\Console\Command\ApplicationGenerator);
			$this->add(new \Strukt\Console\Command\ApplicationLoaderGenerator);
			
			if($registry->exists("module-list")){

				$this->add(new \Strukt\Console\Command\RouterGenerator);
				$this->add(new \Strukt\Console\Command\ModuleGenerator);
				$this->add(new \Strukt\Console\Command\RouteList);
			}

			$this->add(new \Strukt\Console\Command\ShellExec);
			$this->addCmdSect("\nPackage");
			$this->add(new \Strukt\Console\Command\PackagePublisher);
			$this->add(new \Strukt\Console\Command\PackageMake);
			$this->add(new \Strukt\Console\Command\PackageAdd);
			$this->add(new \Strukt\Console\Command\PackageCopy);
			$this->add(new \Strukt\Console\Command\PackageExport);
		}
	}
}