<?php

namespace App\Form\Validation;

/**
* Validation Factory class
*
* @author Moderator <pitsolu@gmail.com>
*/
class Factory{

	/**
	* Constructor
	*/
	private function __construct(){

		//Just be private
	}

	/**
	* Getter for Factory Instance
	*
	* @return App\Form\Validation\Factory
	*/
	static public function getInstance(){

		return new Factory();
	}

	/**
	* Build new Validator
	*
	* @param string $val
	*
	* @return App\Form\Validation\Validator
	*/
	public function newValidator($val=null){

		return new Validator($val);
	}
}