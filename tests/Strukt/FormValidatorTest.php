<?php

use PHPUnit\Framework\TestCase;

class FormValidatorTest extends TestCase{

	public function testLen(){

		$input = "Moderator";

		$validator = new \App\Validator($input);
		$validator = $validator->isLen(9)->isLenGt(8)->isNotEmpty();

		$messages = $validator->getMessage();

		$this->assertEquals($messages, array(

			"is_valid_length"=>true,
			"is_not_empty"=>true,
			"is_gt"=>true
		));
	}

	public function testIsLenFail(){

		$validator = new \App\Validator("Moderator");
		$validator = $validator->isLen(45)->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_valid_length"=>false,
			"is_not_empty"=>true
		));
	}

	public function testIsAlpha(){

		$validator = new \App\Validator("moderator");
		$validator = $validator->isAlpha()->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alpha"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsAlphaSpaced(){

		$validator = new \App\Validator("pitsolu moderator");
		$validator = $validator->isAlpha()->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alpha"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsAlphaNum(){

		$validator = new \App\Validator("pa55w0rd");
		$validator = $validator->isAlphaNum()->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alphanum"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsAlphaNumFail(){

		$validator = new \App\Validator("p@55w0rd");
		$validator = $validator->isAlphaNum()->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alphanum"=>false,
			"is_not_empty"=>true
		));
	}

	public function testIsEmail(){

		$validator = new \App\Validator("pitsolu@gmail.com");
		$validator = $validator->isEmail()->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_email"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsDate(){

		$date = new DateTime("now");

		$validator = new \App\Validator($date->format("Y-m-d"));
		$validator = $validator->isDate()->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_date"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsIn(){

		$validator = new \App\Validator("Ron");
		$validator = $validator->isIn(array("Obama", "Romney", "Hilary", "Ron"))
								->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_not_empty"=>true,
			"in_enum"=>true
		));
	}

	public function testEqualsTo(){

		$validator = new \App\Validator(sha1("p@55w0rd"));
		$validator = $validator->equalTo(sha1("p@55w0rd"))
								->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"equal_to"=>true,
			"is_not_empty"=>true
		));
	}
}