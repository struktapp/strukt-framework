<?php

namespace App\Form;

/**
* Data class to be inherited in Form
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Data{

	/**
	* Messages from validators
	*
	* @return array
	*/
	private $message;

	/**
	* Raw request values
	*
	* @return array
	*/
	private $rawVals;

	/**
	* Flag to check if is test mode
	*
	* @return array
	*/
	private $isTestMode = false;

	/**
	* Constructor
	*
	* @param $rawVals Array of values
	*/
	public function __construct(array $rawVals=null){

		if(!is_null($rawVals)){

			$this->rawVals = $rawVals;
			$this->isTestMode = true;
		}
	}

	/**
	* Getter for validator factory
	*
	* @return App\Form\Validation\Factory
	*/
	protected function getValidationFactory(){

		return \App\Form\Validation\Factory::getInstance();
	}

	/**
	* Getter for request parameters
	*
	* @param string $key
	*
	* @throws \Exception
	* @return string
	*/
	protected function getParam($key){

		if($this->isTestMode)
			return $this->rawVals[$key];

		$registry = \Strukt\Core\Registry::getInstance();

		if(!$registry->exists("request"))
			throw new \Exception("Server Request object (key:[request]) is not in in Strukt\Core\Registy!");

		$request = $registry->get("request");

		// $body = $request->getParsedBody();

		// if($body instanceof \Psr\Http\Message\StreamInterface)
			// $body = json_decode((string)$body, 1);

		return $request->request->get($key);
	}

	/**
	* Message setter
	*
	* @param string $key request parameter name
	* @param App\Form\Validation\Validator $validator
	*
	* @return void
	*/
	protected function setMessage($key, Validation\Validator $validator){

		$this->message[$key] = $validator->getMessage();
		$this->rawVals[$key] = $validator->getVal();
	}

	/**
	* Getter raw validator values
	*
	* @param string $key
	*
	* @return string
	*/
	public function get($key){

		return $this->rawVals[$key];
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