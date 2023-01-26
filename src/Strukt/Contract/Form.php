<?php

namespace Strukt\Contract;

use Strukt\Http\Request;
use \Strukt\Framework\Service\Validator\Injectable;
// use Strukt\Validator;

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
	// private $message;

	/**
	* Http Request
	*
	* @return Strukt\Http\Request
	*/
	private $request;

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
	// protected function getValidatorService(){

		// return $this->core()->get("strukt.service.validator");
	// }

	/**
	* Message setter
	*
	* @param string $key request parameter name
	* @param Strukt\Validator $validator
	*
	* @return void
	*/
	// protected function setMessage($key, Validator $validator){

		// $this->message[$key] = $validator->getMessage();
	// }

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
	// protected function validation(){

		//do validation
	// }

	/**
	* Execute validator and return compiled messages
	*
	* @return array
	*/
	public function validate(){

		$rForm = new \ReflectionClass($this);
		$rInj = new Injectable($rForm);

		$factory = $this->core()->get("strukt.service.validator");

		foreach($rInj->getConfigs() as $key=>$props){

			$service = $factory->getNew($this->request->get($key));

			$ref = \Strukt\Ref::createFrom($service);
			foreach($props as $vName=>$prop)
				$service = $ref->method(lcfirst($vName))->invoke();

			$message[$key] = $service->getMessage();
		}

		foreach($message as $field=>$props)
      		if(!array_product(array_values($props)))
          			return array("is_valid"=>false, "messages"=>$message);

    	return array("is_valid"=>true, "messages"=>"None");
	}
}