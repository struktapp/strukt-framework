<?php

use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase{

	public function setUp(){

		$appCfg = parse_ini_file("cfg/app.ini");

		$this->appName = $appCfg["app-name"];
		$this->authModuleUserNs = sprintf("%s\AuthModule\Form\User", $this->appName);
	}

	public function testSuccessfulValidation(){

		$role = array(

			"sadmin"=>1,
			"admin"=>2
		);

		$vals = array(

			"username"=>"admin",
			"password"=>"p@55w0rd",
			"confirm"=>"p@55w0rd",
			"role"=>$role["sadmin"]
		);

		$refCls = new ReflectionClass($this->authModuleUserNs);
		$form = $this->authModuleUser = $refCls->newInstanceArgs(array($vals));
		$validation = $form->validate();

		$this->assertTrue($validation["is_valid"]);
		$this->assertEquals("None", $validation["messages"]);
	}

	public function testFailedValidation(){

		$role = array(

			"sadmin"=>1,
			"admin"=>2
		);

		$vals = array(

			"username"=>"admin",
			"password"=>"",
			"confirm"=>"p@55w0rd_",//Wrong Confirm Password
			"role"=>$role["sadmin"]
		);

		$refCls = new ReflectionClass($this->authModuleUserNs);
		$form = $this->authModuleUser = $refCls->newInstanceArgs(array($vals));
		$validation = $form->validate();

		$this->assertFalse($validation["is_valid"]);

		extract($validation["messages"]);

		$this->assertFalse($password["is_not_empty"]);
	}
}