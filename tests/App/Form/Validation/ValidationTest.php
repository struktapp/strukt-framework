<?php

class ValidationTest extends PHPUnit_Framework_TestCase{

	public function testSuccessfulValidation(){

		$role = array(

			"sadmin"=>1,
			"admin"=>2
		);

		$form = new Payroll\AuthModule\Form\User();
		$form->setParam("password", "p@55w0rd");
		$form->setParam("confirm", "p@55w0rd");
		$form->setParam("role", $role["sadmin"]);

		$validation = $form->validate();

		$this->assertTrue($validation["is_valid"]);
		$this->assertEquals("None", $validation["messages"]);
	}

	public function testFailedValidation(){

		$role = array(

			"sadmin"=>1,
			"admin"=>2
		);

		$form = new Payroll\AuthModule\Form\User();
		$form->setParam("password", "p@55w0rd");
		$form->setParam("confirm", "p@55w0rd_");//Wrong Confirm Password
		$form->setParam("role", $role["sadmin"]);

		$validation = $form->validate();

		$this->assertFalse($validation["is_valid"]);

		extract($validation["messages"]);

		$this->assertTrue($password["is_not_empty"]);
		$this->assertTrue($confirm["is_not_empty"]);
		$this->assertFalse($password_match["equal_to"]);
		$this->assertTrue($role["is_num"]);
	}
}