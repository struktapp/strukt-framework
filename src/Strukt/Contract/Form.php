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
			foreach($props as $vName=>$prop){

				$rMethod = $ref->method(lcfirst($vName));

				$items = $prop["item"];
				if(array_key_exists("items", $prop))
					$items = $prop["items"];

				if(!empty($items))
					$rMethod->invoke($items);
				else
					$rMethod->invoke();
			}

			$message[$key] = $service->getMessage();
		}

		foreach($message as $field=>$props)
      		if(!array_product(array_values($props)))
          			return array("is_valid"=>false, "messages"=>$message);

    	return array("is_valid"=>true, "messages"=>"None");
	}
}