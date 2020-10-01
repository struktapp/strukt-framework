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
        	array("password", null, "p@55w0rd")
        )));

		$loginFrm = new class($request) extends \Strukt\Contract\Form{

			protected function validation(){

				$service = $this->getValidatorService();

				$this->setMessage("email", $service->getNew($this->get("email"))
												->isNotEmpty()
												->isEmail());

				$this->setMessage("password", $service->getNew($this->get("password"))
												->isNotEmpty()
												->isLen(8));

			}
		};

		$messages = $loginFrm->validate();

		$this->assertEquals($messages, array(

			"is_valid"=>true,
			"messages"=>"None"
		));
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

			protected function validation(){

				$service = $this->getValidatorService();

				$this->setMessage("username", $service->getNew($this->get("username"))
												->isNotEmpty()
												->isEmail());

				$this->setMessage("password", $service->getNew($this->get("password"))
												->isNotEmpty()
												->isLen(8));

				$this->setMessage("confirm_password", $service->getNew($this->get("confirm_password"))
												->isNotEmpty()
												->equalTo($this->get("password")));

			}
		};

		$messages = $loginFrm->validate();

		$this->assertFalse($messages["is_valid"]);
	}
}