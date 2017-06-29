<?php

class ApplicationTest extends PHPUnit_Framework_TestCase{

	public function testModuleOutput(){

		$authModule = new Payroll\AuthModule\PayrollAuthModule();

		$this->assertEquals($authModule->getAlias(), "Au");
		$this->assertEquals($authModule->getNamespace(), "Payroll\AuthModule\PayrollAuthModule");
		$this->assertEquals($authModule->getBaseDir(), realpath(".")."/app/src/Payroll/AuthModule");

		return $authModule;
	}

	/**
	* @depends testModuleOutput
	*/
	public function testNameRegistryOutput($authModule){

		$app = new Strukt\Application();
		$app->register($authModule);

		$nr = $app->getNameRegistry();

		$this->assertInstanceOf("Strukt\Core\Map", $nr);
		$this->assertEquals($nr->get("au.name"), "AuthModule");
		$this->assertEquals($nr->get("au.fname"), "PayrollAuthModule");
		$this->assertEquals($nr->get("au.app.name"), "Payroll");
		$this->assertEquals($nr->get("au.ns"), "Payroll\AuthModule\PayrollAuthModule");
		$this->assertEquals($nr->get("au.base.ns"), "Payroll\AuthModule");

		$auRtr = $nr->get("au.rtr");

		$rtr = new Strukt\Core\Collection($auRtr->getName());
		$rtr->set("Auth", "Payroll\AuthModule\Router\Auth");
		$rtr->set("Index", "Payroll\AuthModule\Router\Index");

		$this->assertEquals($auRtr, $rtr);

		return $app;
	}

	/**
	* @depends testNameRegistryOutput
	*/
	public function testModuleCoreStaticClass($app){

		$r = \Strukt\Framework\Registry::getInstance();
		$r->set("nr", $app->getNameRegistry());
		$r->set("core", new Strukt\Framework\Module\Core());

		$userController = $r->get("core")->get("au.ctr.User");

		// print_r($userController->doAuthentication("admin","p@55w0rd"));

		$this->assertTrue($userController->doAuthentication("admin","p@55w0rd"), array(

			"admin",
			"p@55w0rd"
		));

		// $this->assertFalse($userController->isAuthd());

		// return $core;
	}

	/**
	* @depends testModuleCoreStaticClass
	*/
	public function testModuleCoreNewClass(/*$core*/){

		$r = \Strukt\Framework\Registry::getInstance();

		$userModel = $r->get("core")->getNew("au.mdl.User", array("admin", "p@55w0rd"));

		$this->assertEquals("admin", $userModel->getUsername());
		$this->assertEquals(sha1("p@55w0rd"), $userModel->getPassword());
	}
}