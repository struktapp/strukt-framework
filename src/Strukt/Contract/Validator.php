<?php

namespace Strukt\Contract;

use Strukt\Core\Registry;

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
	protected $val;

	/**
	* Failure or success messages for each condition
	*
	* @var array
	*/
	protected $message = [];

	/**
	* Constructor get validation value
	*/
	public function __construct($val=null){

		if(!is_null($val))
			$this->setVal($val);
	}

	/**
	* Application registry
	*
	* @return Strukt\Core\Registry
	*/
	protected function core(){

		return Registry::getSingleton();
	}

	/**
	* Setter for validation value
	*
	* @param string $val
	*
	* @return Strukt\Validator
	*/
	public function setVal($val){

		$this->val = $val;

		return $this;
	}

	/**
	* Getter for validation value
	*
	* @return string
	*/
	public function getVal(){

		return $this->val;
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