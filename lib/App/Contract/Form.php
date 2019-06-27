<?php

namespace App\Contract;

use Strukt\Http\Request;
use App\Form\ValidatorFactory;
use App\Form\Validator;

/**
* Form class to be inherited in Form
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Form{

	/**
	* Messages from validators
	*
	* @return array
	*/
	private $message;

	/**
	* Constructor
	*
	* @param $request Strukt\Http\Request
	*/
	public function __construct(Request $request){

		$this->request = $request;
	}

	/**
	* Getter for validator factory
	*
	* @return App\Form\Validation\Factory
	*/
	protected function getValidatorFactory(){

		return ValidatorFactory::getInstance();
	}

	/**
	* Message setter
	*
	* @param string $key request parameter name
	* @param App\Form\Validator $validator
	*
	* @return void
	*/
	protected function setMessage($key, Validator $validator){

		$this->message[$key] = $validator->getMessage();
	}

	/**
	* Getter raw validator values
	*
	* @param string $key
	*
	* @return string
	*/
	public function get($key){

		return $this->request->get($key);
	}

	/**
	* Validation method to be overriden
	*
	* @return void
	*/
	protected function validation(){

		//do validation
	}

	/**
	* Execute validator and return compiled messages
	*
	* @return array
	*/
	public function validate(){

		$this->validation();

		foreach($this->message as $field=>$props)
      		foreach($props as $prop)
        		if(!$prop)
          			return array("is_valid"=>false, "messages"=>$this->message);

    	return array("is_valid"=>true, "messages"=>"None");
	}
}