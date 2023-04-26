<?php

use PHPUnit\Framework\TestCase;
use Strukt\Core\Registry;
use Strukt\Framework\Provider\Validator as ValidatorService;
use Strukt\Http\Request;

class FormValidationFactoryTest extends TestCase{

	public function setUp():void{

		$factory = new ValidatorService(); 
		$factory->register();
	}

	/**
     * @runInSeparateProcess
     */
	public function testValidForm(){

		$request = $this->createMock(Request::class);
        $request->method('get')->will($this->returnValueMap(array(

        	array("email", null, "pitsolu@gmail.com"),
        	array("password", null, "p@55w0rd"),
        	array("confirm_password", null, "p@55w0rd")
        )));

		$loginFrm = new class($request) extends \Strukt\Contract\Form{

			/**
			* @IsEmail()
			* @IsNotEmpty()
			*/
			public $email;

			/**
			* @IsNotEmpty()
			* @IsLen(8)
			*/
			public $password;

			/**
			* @EqualTo(.password)
			* @IsNotEmpty()
			*/
			public $confirm_password;
		};

		$messages = $loginFrm->validate();

		// print_r($messages);

		$this->assertEquals($messages, array(

			"success"=>true,
			"message"=>"None"
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testEmptyForm(){

		$request = $this->createMock(Request::class);
        $request->method('get')->will($this->returnValueMap(array(

        	array("email", null, ""),
        	array("password", null, ""),
        	array("confirm_password", null, "")
        )));

		$loginFrm = new class($request) extends \Strukt\Contract\Form{

			/**
			* @IsEmail()
			* @IsNotEmpty()
			*/
			public $email;

			/**
			* @IsNotEmpty()
			* @IsLen(8)
			*/
			public $password;

			/**
			* @EqualTo(.password)
			* @IsNotEmpty()
			*/
			public $confirm_password;
		};

		$messages = $loginFrm->validate();

		// print_r($messages);

		$this->assertFalse($messages["success"]);
	}

	/**
     * @runInSeparateProcess
     */
	public function testInvalidForm(){

		$request = $this->createMock(Request::class);
        $request->method('get')->will($this->returnValueMap(array(

        	array("username", null, "pitsolu"),
        	array("password", null, "p@55w0rd"),
        	array("confirm_password", null, "PaSsW0rd")
        )));

		$loginFrm = new class($request) extends \Strukt\Contract\Form{

			/**
			* @IsEmail()
			* @IsNotEmpty()
			*/
			public $username;

			/**
			* @IsLen(8)
			* @IsNotEmpty()
			*/
			public $password;

			/**
			* @EqualTo(.username)
			* @IsNotEmpty()
			*/
			public $confirm_password;
		};

		$messages = $loginFrm->validate();

		// print_r($messages);

		$this->assertFalse($messages["success"]);
	}
}