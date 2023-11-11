<?php

namespace Strukt\Traits;

/**
* Validator class
*
* @author Moderator <pitsolu@gmail.com>
*/
trait Validator{

	/**
	* Check is value is alpha
	*
	* @return Strukt\Framework\Validator
	*/
	public function isAlpha(){

		$this->message["is_alpha"] = false;
		if(preg_match("/^[A-Za-z_]+$/", trim($this->getValue())))
			$this->message["is_alpha"] = true;

		return $this;
	}

	/**
	* Check is value is alphanumeric
	*
	* @return Strukt\Framework\Validator
	*/
	public function isAlphaNum(){

		$this->message["is_alphanum"] = false;
		if(ctype_alnum(str_replace(" ", "", $this->getValue())))
			$this->message["is_alphanum"] = true;

		return $this;
	}

	/**
	* Check is value is numeric
	*
	* @return Strukt\Framework\Validator
	*/
	public function isNumeric(){

		$this->message["is_num"] = false;
		if(is_numeric($this->getValue()))
			$this->message["is_num"] = true;

		return $this;
	}

	/**
	* Check is value is email
	*
	* @return Strukt\Framework\Validator
	*/
	public function isEmail(){

		$this->message["is_email"] = false;
		if(filter_var($this->getValue(), FILTER_VALIDATE_EMAIL))
			$this->message["is_email"] = true;

		return $this;
	}

	/**
	* Check is value is date
	*
	* @return Strukt\Framework\Validator
	*/
	public function isDate($format="Y-m-d"){

		$date = \DateTime::createFromFormat($format, $this->getValue());
		$err = \DateTime::getLastErrors();

		$this->message["is_date"] = false;
		if($err['warning_count'] == 0 && $err['error_count'] == 0)
			$this->message["is_date"] = true;

		return $this;
	}

	/**
	* Check is value is not empty
	*
	* @return Strukt\Framework\Validator
	*/
	public function isNotEmpty(){

		$this->message["is_not_empty"] = true;
		if(empty($this->getValue()))
			$this->message["is_not_empty"] = false;

		return $this;
	}

	/**
	* Check is value is in enumerator
	*
	* @return Strukt\Framework\Validator
	*/
	public function isIn($enum){

		if(!is_array($enum))
			throw new \Exception(sprintf("%s::isIn only takes an array!", Validator::class));

		$this->message["in_enum"] = false;
		if(in_array($this->getValue(), $enum))
			$this->message["in_enum"] = true;

		return $this;
	}

	/**
	* Check values are equal
	*
	* @return Strukt\Framework\Validator
	*/
	public function equalTo($val){

		$this->message["equal_to"] = true;
		if($val !== $this->getValue())
			$this->message["equal_to"] = false;

		return $this;
	}

	/**
	* Check length
	*
	* @return Strukt\Framework\Validator
	*/
	public function isLen($len){

		$this->message["is_valid_length"] = false;
		if(strlen($this->getValue()) == $len)
			$this->message["is_valid_length"] = true;

		return $this;
	}
}