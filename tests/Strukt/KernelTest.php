<?php

use PHPUnit\Framework\TestCase;
use Strukt\Framework\Configuration;
use Strukt\Framework\Injectable;
use Strukt\Framework\App as FrameworkApp;
use Strukt\Router\Kernel;
use Strukt\Http\Request;
use Strukt\Http\Session;
use Strukt\Env;
use Strukt\Core\Registry;

class KernelTest extends TestCase{

	public function setUp():void{

		// Env::set("root_dir", getcwd());
		Env::set("rel_app_ini", "cfg/app.ini");
		Env::set("rel_static_dir", "public/static");
		Env::set("rel_mod_ini", "cfg/module.ini");
		Env::set("is_dev", true);
	}
 
	public function testBoilerPlate(){

		$this->kernel = new Kernel(Request::createFromGlobals());

		$this->kernel->providers([

			\Strukt\Framework\Provider\Validator::class,
			\Strukt\Framework\Provider\Annotation::class,
			\Strukt\Framework\Provider\Router::class
		]);

		$this->kernel->inject("app.dep.author", function(){

			return array();
		});

		$this->kernel->inject("app.dep.authentic", function(Session $session){

			$user = new Strukt\User();
			$user->setUsername($session->get("username"));

			return $user;
		});

		$this->kernel->inject("app.dep.session", function(){

			return new Session;
		});

		$this->kernel->middlewares([

			\Strukt\Router\Middleware\ExceptionHandler::class,
			\Strukt\Router\Middleware\Session::class,
			\Strukt\Router\Middleware\Authorization::class,
			\Strukt\Router\Middleware\Authentication::class,
			\Strukt\Router\Middleware\StaticFileFinder::class,
			\Strukt\Router\Middleware\Router::class
		]);

		$loader = new \App\Loader($this->kernel);
		$app = $loader->getApp(); 
		$app->initialize();

		// $registry = Registry::getSingleton();
		// print_r($registry->get("app.service.router"));
		// print_r($registry->get("app.router"));
	}
}