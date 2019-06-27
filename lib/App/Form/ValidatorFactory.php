<?php

namespace App\Form;

/**
* Validation Factory class
*
* @author Moderator <pitsolu@gmail.com>
*/
class ValidatorFactory{

	/**
	* Constructor
	*/
	private function __construct(){

		//Just be private
	}

	/**
	* Getter for Factory Instance
	*
	* @return App\Form\ValidatorFactory
	*/
	static public function getInstance(){

		return new ValidatorFactory();
	}

	/**
	* Build new Validator
	*
	* @param string $val
	*
	* @return App\Form\ValidatorFactory
	*/
	public function newValidator($val=null){

		return new Validator($val);
	}
}