<?php

namespace Strukt\Package;

class Core implements \Strukt\Framework\Contract\Package{

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

					\Strukt\Framework\Provider\Validator::class,
				),
				"middlewares"=>array(

					\App\Middleware\Cors::class,
					\Strukt\Router\Middleware\Session::class,
					\Strukt\Router\Middleware\Authorization::class,
					\Strukt\Router\Middleware\Authentication::class,
					\Strukt\Framework\Middleware\Validator::class,
				)
			),
			"App:Cli"=>array(

				"providers"=>array(

					\Strukt\Framework\Provider\Validator::class,
				),
				"middlewares"=>array(

					\App\Middleware\XSession::class,
					\Strukt\Router\Middleware\Authentication::class,
					\Strukt\Framework\Middleware\Validator::class,
				),
				"commands"=>array(

					\Strukt\Framework\Console\Command\PackagePublisher::class,
					\Strukt\Framework\Console\Command\PackageList::class,
					\Strukt\Framework\Console\Command\PackageInfo::class,
					\Strukt\Framework\Console\Command\PackageMake::class,
					\Strukt\Framework\Console\Command\PackageAdd::class,
					\Strukt\Framework\Console\Command\PackageCopy::class,
					\Strukt\Framework\Console\Command\PackageExport::class
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