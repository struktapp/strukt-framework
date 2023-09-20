<?php

namespace Strukt\Framework\Contract;

/**
* Abstract Validator
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Validator{

	/**
	* Value to undergo validation
	*
	* @var string
	*/
	protected $value;

	/**
	* Failure or success messages for each condition
	*
	* @var array
	*/
	protected $message = [];

	/**
	* Constructor get validation value
	*/
	public function __construct($value=null){

		if(!is_null($value))
			$this->setValue($value);
	}

	/**
	* Setter for validation value
	*
	* @param string $val
	*
	* @return Strukt\Validator
	*/
	public function setValue($value){

		$this->value = $value;

		return $this;
	}

	/**
	* Getter for validation value
	*
	* @return string
	*/
	public function getValue(){

		return $this->value;
	}

	/**
	* Getter for messages
	*
	* @return array
	*/
	public function getMessage(){

		return $this->message;
	}
}