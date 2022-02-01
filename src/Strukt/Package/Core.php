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

					\Strukt\Framework\Provider\Validator::class,
					\Strukt\Framework\Provider\Annotation::class,
					\Strukt\Framework\Provider\Router::class
				),
				"middlewares"=>array(

					\App\Middleware\Cors::class,
					\Strukt\Router\Middleware\ExceptionHandler::class,
					\Strukt\Router\Middleware\Session::class,
					\Strukt\Router\Middleware\Authorization::class,
					\Strukt\Router\Middleware\Authentication::class,
					\Strukt\Router\Middleware\Router::class
				)
			),
			"App:Cli"=>array(

				"providers"=>array(

					\Strukt\Framework\Provider\Validator::class,
					\Strukt\Framework\Provider\Annotation::class,
					\Strukt\Framework\Provider\Router::class
				),
				"middlewares"=>array(

					\Strukt\Router\Middleware\Router::class
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
}