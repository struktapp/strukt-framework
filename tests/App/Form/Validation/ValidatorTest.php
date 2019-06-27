<?php

use PHPUnit\Framework\TestCase;
use App\Form\ValidatorFactory;

class ValidatorTest extends TestCase{

	public function setUp(){

		$this->factory = ValidatorFactory::getInstance();
	}

	public function testIsLen(){

		$validator = $this->factory->newValidator()
					->setVal("Moderator")
					->isLen(9)
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_valid_length"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsLenFail(){

		$validator = $this->factory->newValidator()
					->setVal("Moderator")
					->isLen(45)
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_valid_length"=>false,
			"is_not_empty"=>true
		));
	}

	public function testIsAlpha(){

		$validator = $this->factory->newValidator()
					->setVal("moderator")
					->isAlpha()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alpha"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsAlphaSpaced(){

		$validator = $this->factory->newValidator()
					->setVal("pitsolu moderator")
					->isAlpha()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alpha"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsAlphaNum(){

		$validator = $this->factory->newValidator()
					->setVal("pa55w0rd")
					->isAlphaNum()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alphanum"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsAlphaNumFail(){

		$validator = $this->factory->newValidator()
					->setVal("p@55w0rd")
					->isAlphaNum()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alphanum"=>false,
			"is_not_empty"=>true
		));
	}

	public function testIsEmail(){

		$validator = $this->factory->newValidator()
					->setVal("pitsolu@gmail.com")
					->isEmail()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_email"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsDate(){

		$date = new DateTime("now");

		$validator = $this->factory->newValidator()
					->setVal($date->format("Y-m-d"))
					->isDate()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_date"=>true,
			"is_not_empty"=>true
		));
	}

	public function testIsIn(){

		$validator = $this->factory->newValidator()
					->setVal("Ron")
					->isIn(array("Obama", "Romney", "Hilary", "Ron"))
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_not_empty"=>true,
			"in_enum"=>true
		));
	}

	public function testEqualsTo(){

		$validator = $this->factory->newValidator()
					->setVal(sha1("p@55w0rd"))
					->equalTo(sha1("p@55w0rd"))
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"equal_to"=>true,
			"is_not_empty"=>true
		));
	}
}