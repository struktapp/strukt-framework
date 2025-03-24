<?php

use PHPUnit\Framework\TestCase;
use Strukt\Framework\Application;
use Strukt\Router\Kernel as Router;
use Strukt\Http\Request;
use Strukt\Fs;
use Strukt\Ref;
use Strukt\Framework\Core;

class ApplicationTest extends TestCase{

	private $core;
	private $auth_mod_name;
	private $auth_mod_ns;
	private $auth_mod_path;
	private $auth_mod_base_ns;
	private $auth_mod;

	public function setUp():void{

		$app_name = config("app.name");

		$this->auth_mod_name = sprintf("%sAuthModule", $app_name);
		$this->auth_mod_ns = sprintf("%s\AuthModule\%s", $app_name, $this->auth_mod_name);
		$this->auth_mod_path = sprintf("%s/app/src/%s/AuthModule", realpath("."), $app_name);
		$this->auth_mod_base_ns = sprintf("%s\AuthModule", $app_name);

		$this->auth_mod = Ref::create($this->auth_mod_ns)->make()->getInstance();

		$facet = new App\Provider\Facet();
		$facet->register();

		$app = new Application(new Router(Request::createFromGlobals()));
		$app->register($this->auth_mod);
		$app->init();

		$this->core = new class() extends Core{

			public function get(string $alias_ns, array $args = null):object{

				return parent::get($alias_ns, $args);
			}
		};
	}


	/**
	* @runInSeparateProcess
	*/
	public function testModuleOutput(){

		$this->assertEquals($this->auth_mod->getAlias(), "Au");
		$this->assertEquals($this->auth_mod->getNamespace(), $this->auth_mod_ns);
		$this->assertEquals($this->auth_mod->getBaseDir(), Fs::ds($this->auth_mod_path));
	}

	/**
	* @runInSeparateProcess
	*/
	public function testNameRegistryOutput(){

		$routes[] = sprintf("%s\Router\Auth", $this->auth_mod_base_ns);
		$routes[] = sprintf("%s\Router\Index", $this->auth_mod_base_ns);

		$x = reg("nr.routes.au");
		$y = $routes;

		sort($x);
		sort($y);

		$this->assertEquals($x, $y);
	}

	/**
	* @runInSeparateProcess
	*/
	public function testModuleCoreStaticClass(){

		$user_ctr = $this->core->get("au.ctr.User");

		$this->assertTrue($user_ctr->doAuth("admin", sha1("p@55w0rd")));
	}
}