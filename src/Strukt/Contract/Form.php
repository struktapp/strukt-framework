<?php

namespace Strukt\Contract;

use Strukt\Http\Request;
use Strukt\Validator;

/**
* Form class to be inherited in Form
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Form extends AbstractCore{

	/**
	* Messages from validators
	*
	* @return array
	*/
	private $message;

	/**
	* Http Request
	*
	* @return Strukt\Http\Request
	*/
	private $request;

	/**
	* Strukt\Core\Registry
	*
	* @return Strukt\Core\Registry
	*/
	private $registry;

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
	* @return anonymous class object
	*/
	protected function getValidatorService(){

		return $this->core()->get("strukt.service.validator");
	}

	/**
	* Message setter
	*
	* @param string $key request parameter name
	* @param Strukt\Validator $validator
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
      		if(!array_product(array_values($props)))
          			return array("is_valid"=>false, "messages"=>$this->message);

    	return array("is_valid"=>true, "messages"=>"None");
	}
}