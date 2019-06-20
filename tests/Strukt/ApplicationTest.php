<?php

use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase{

	public function setUp(){

		$appCfg = parse_ini_file("cfg/app.ini");

		$this->appName = $appCfg["app-name"];
		$this->authModuleName = sprintf("%sAuthModule", $appCfg["app-name"]);
		$this->authModuleNs = sprintf("%s\AuthModule\%s", $this->appName, $this->authModuleName);
		$this->authModulePath = sprintf("%s/app/src/%s/AuthModule", realpath("."), $this->appName);
		$this->authModuleBaseNs = sprintf("%s\AuthModule", $this->appName);
	}

	public function testModuleOutput(){
		
		$refCls = new ReflectionClass($this->authModuleNs);
		$authModule = $refCls->newInstance();

		$this->assertEquals($authModule->getAlias(), "Au");
		$this->assertEquals($authModule->getNamespace(), $this->authModuleNs);
		$this->assertEquals($authModule->getBaseDir(), $this->authModulePath);

		return $authModule;
	}

	/**
	* @depends testModuleOutput
	*/
	public function testNameRegistryOutput($authModule){

		$app = new Strukt\Application(new Strukt\Router\Kernel(Request::createFromGlobals()));
		$app->register($authModule);

		$nr = $app->getNameRegistry();

		$this->assertInstanceOf("Strukt\Core\Map", $nr);
		$this->assertEquals($nr->get("au.name"), "AuthModule");
		$this->assertEquals($nr->get("au.fname"), $this->authModuleName);
		$this->assertEquals($nr->get("au.app.name"), $this->appName);
		$this->assertEquals($nr->get("au.ns"), $this->authModuleNs);
		$this->assertEquals($nr->get("au.base.ns"), sprintf("%s\AuthModule", $this->appName));

		$auRtr = $nr->get("au.rtr");

		$rtr = new Strukt\Core\Collection($auRtr->getName());
		$rtr->set("Auth", sprintf("%s\Router\Auth", $this->authModuleBaseNs));
		$rtr->set("Index", sprintf("%s\Router\Index", $this->authModuleBaseNs));

		$this->assertEquals($auRtr, $rtr);

		return $app;
	}

	/**
	* @depends testNameRegistryOutput
	*/
	public function testModuleCoreStaticClass($app){

		$r = \Strukt\Core\Registry::getInstance();
		$r->set("nr", $app->getNameRegistry());
		$r->set("core", new Strukt\Framework\Module\Core());

		$userController = $r->get("core")->get("au.ctr.User");

		$this->assertTrue($userController->doAuthentication("admin","p@55w0rd"));
	}
}