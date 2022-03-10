<?php

use PHPUnit\Framework\TestCase;
use Strukt\Framework\Configuration;
use Strukt\Framework\Injectable;
use Strukt\Framework\App as FrameworkApp;
use Strukt\Router\Kernel;
use Strukt\Http\Request;
use Strukt\Http\Session\Native as Session;
use Strukt\Env;
use Strukt\Core\Registry;

class RouterKernelTest extends TestCase{

	public function setUp():void{

		//		
	}
 
 	/**
    * @runInSeparateProcess
    */
	public function testBoilerPlate(){

		$this->kernel = new Kernel(Request::createFromGlobals());

		$this->kernel->providers([

			\Strukt\Framework\Provider\Validator::class,
			\Strukt\Framework\Provider\Annotation::class,
			\Strukt\Framework\Provider\Router::class
		]);

		$this->kernel->inject("@inject.permissions", function(){

			return array();
		});

		$this->kernel->inject("@inject.verify", function(Session $session){

			$user = new Strukt\User();
			$user->setUsername($session->get("username"));

			return $user;
		});

		$this->kernel->inject("@inject.session", function(){

			return new Session;
		});

		$this->kernel->middlewares([

			\Strukt\Router\Middleware\ExceptionHandler::class,
			\Strukt\Router\Middleware\Session::class,
			\Strukt\Router\Middleware\Authorization::class,
			\Strukt\Router\Middleware\Authentication::class,
			//\Strukt\Middleware\Asset::class,
			\Strukt\Router\Middleware\Router::class
		]);

		$loader = new \App\Loader($this->kernel);
		$app = $loader->getApp(); 
		$app->run()->init();

		$_SERVER["REQUEST_URI"] = "/";

		$response = $this->kernel->run();

		$this->assertEquals("</b>Strukt Works!<b>", $response->getContent());
	}
}