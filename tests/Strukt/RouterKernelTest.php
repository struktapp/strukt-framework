<?php

use PHPUnit\Framework\TestCase;
use Strukt\Router\Kernel;
use Strukt\Http\Request;
use Strukt\Http\Session\Native as Session;

class RouterKernelTest extends TestCase{

	private $kernel;
 
 	/**
    * @runInSeparateProcess
    */
	public function testBoilerPlate(){

		$this->kernel = new Kernel(Request::createFromGlobals());

		$this->kernel->providers([

			\Strukt\Framework\Provider\Validator::class
		]);

		$this->kernel->inject("permissions", function(){

			return array();
		});

		$this->kernel->inject("verify", function(Session $session){

			$user = new Strukt\User();
			$user->setUsername($session->get("username"));

			return $user;
		});

		$this->kernel->inject("session", function(){

			return new Session;
		});

		$this->kernel->middlewares([

			\Strukt\Router\Middleware\Session::class,
			\Strukt\Router\Middleware\Authorization::class,
			\Strukt\Router\Middleware\Authentication::class,
		]);

		$loader = new \App\Loader($this->kernel);
		$app = $loader->getApp(); 

		$_SERVER["REQUEST_URI"] = "/";

		$response = $app->run();

		$this->assertEquals("</b>Strukt Works!<b>", $response);
	}
}