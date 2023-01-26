<?php

namespace Strukt;

use Strukt\Env;
use Strukt\Core\Registry;

use Strukt\Console\Command\ApplicationGenerator;
use Strukt\Console\Command\ApplicationLoaderGenerator;
use Strukt\Console\Command\RouterGenerator;
use Strukt\Console\Command\ModuleGenerator;
use Strukt\Console\Command\RouteList;
use Strukt\Console\Command\ShellExec;
use Strukt\Console\Command\PackagePublisher;
use Strukt\Console\Command\PackageMake;
use Strukt\Console\Command\PackageAdd;
use Strukt\Console\Command\PackageCopy;
use Strukt\Console\Command\PackageExport;

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

			$this->add(new ApplicationGenerator);
			$this->add(new ApplicationLoaderGenerator);
			
			if($registry->exists("module-list")){

				$this->add(new RouterGenerator);
				$this->add(new ModuleGenerator);
				$this->add(new RouteList);
			}

			$this->add(new ShellExec);
			$this->addCmdSect("\nPackage");
			$this->add(new PackagePublisher);
			$this->add(new PackageMake);
			$this->add(new PackageAdd);
			$this->add(new PackageCopy);
			$this->add(new PackageExport);
		}
	}
}