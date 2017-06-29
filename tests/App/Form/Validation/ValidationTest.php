<?php

class ValidationTest extends PHPUnit_Framework_TestCase{

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

		$form = new Payroll\AuthModule\Form\User($vals);
		$validation = $form->validate();

		// print_r($validation);

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

		$form = new Payroll\AuthModule\Form\User($vals);
		$validation = $form->validate();

		// print_r($validation);

		$this->assertFalse($validation["is_valid"]);

		extract($validation["messages"]);

		$this->assertFalse($password["is_not_empty"]);
		// $this->assertTrue($confirm["is_not_empty"]);
		// $this->assertFalse($password_match["equal_to"]);
		// $this->assertTrue($role["is_num"]);
	}
}