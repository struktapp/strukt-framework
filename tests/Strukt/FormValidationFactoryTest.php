<?php

use PHPUnit\Framework\TestCase;
use Strukt\Framework\Contract\Form;
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

		$request = new Request([
			"email"=>"pitsolu@gmail.com",
			"password"=>"p@55w0rd",
			"confirm_password"=>"p@55w0rd"
		]);

		$loginFrm = new class($request) extends Form{

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

		$request = new Request([
			"email"=>"",
			"password"=>"",
			"confirm_password"=>""
		]);

		$loginFrm = new class($request) extends Form{

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

		$request = Request([
			
        	"username" => "pitsolu",
        	"password" => "p@55w0rd",
        	"confirm_password" => "PaSsW0rd"
        ]);

		$loginFrm = new class($request) extends Form{

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