<?php

namespace Strukt\Package;

use Strukt\Contract\Package as Pkg;

class Core implements Pkg{

	private $manifest;

	public function __construct(){

		$this->manifest = array(

			"cmd_name"=>"Strukt",
			"package"=>"core",
			"files"=>null
		);
	}

	public function getSettings($type){

		$settings = array(

			"App:Idx"=>array(

				"providers"=>array(

					\Strukt\Provider\Validator::class,
					\Strukt\Provider\Router::class
				),
				"middlewares"=>array(

					\App\Middleware\Cors::class,
					\Strukt\Middleware\ExceptionHandler::class,
					\Strukt\Middleware\Session::class,
					\Strukt\Middleware\Authorization::class,
					\Strukt\Middleware\Authentication::class,
					\Strukt\Middleware\Validator::class,
					\Strukt\Middleware\Router::class
				)
			),
			"App:Cli"=>array(

				"providers"=>array(

					\Strukt\Provider\Validator::class,
					\Strukt\Provider\Router::class
				),
				"middlewares"=>array(

					\App\Middleware\XSession::class,
					\Strukt\Middleware\Authentication::class,
					\Strukt\Middleware\Validator::class,
					\Strukt\Middleware\Router::class
				),
				"commands"=>array(

					\Strukt\Console\Command\PackagePublisher::class,
					\Strukt\Console\Command\PackageList::class,
					\Strukt\Console\Command\PackageInfo::class,
					\Strukt\Console\Command\PackageMake::class,
					\Strukt\Console\Command\PackageAdd::class,
					\Strukt\Console\Command\PackageCopy::class,
					\Strukt\Console\Command\PackageExport::class
				)
			)
		);

		return $settings[$type];
	}

	public function getName(){

		return $this->manifest["package"];
	}

	public function getCmdName(){

		return $this->manifest["cmd_name"];
	}

	public function getFiles(){

		return $this->manifest["files"];
	}

	public function getModules(){

		return null;
	}

	public function isPublished(){

		return true;
	}

	public function getRequirements(){

		return null;
	}
}