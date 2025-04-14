<?php

namespace Strukt\Framework\Contract;

use Strukt\Http\Request;
use Strukt\Framework\Injectable\Validator as InjectableValidator;
use Strukt\Contract\FacetInterface;

/**
* Form class to be inherited in Form
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Form implements FacetInterface{

	/**
	* Http Request
	*
	* @return Strukt\Http\Request
	*/
	private $request;

	/**
	* Constructor
	*
	* @param \Strukt\Http\Request $request 
	*/
	public function __construct(Request $request){

		$this->request = $request;
	}

	/**
	* Getter raw validator values
	*
	* @param string $key
	*/
	public function get(string $key){

		return $this->request->get($key);
	}

	/**
	* Execute validator and return compiled messages
	*
	* @return array
	*/
	public function validate():array{

		$helper = new class(){use \Strukt\Traits\Collection{

			detach as public toArray;
		}};

		$configs = [];
		if(cache("validator")->empty() || !cache("validator")->exists(get_called_class())){

			$rValdInj = new InjectableValidator(new \ReflectionClass($this));
			$configs = $rValdInj->getConfigs();

			cache("validator")->put(get_called_class(), $configs)->save();
		}

		if(empty($configs))
			$configs = $helper->toArray(cache("validator")->get(get_called_class()));

		$factory = event("provider.validator")->exec();

		foreach($configs as $key=>$props){

			$service = $factory->getNew($this->get($key));

			$ref = \Strukt\Ref::createFrom($service);

			foreach($props as $vName=>$prop){

				$rMethod = null;
				$vName = lcfirst($vName);
				if($ref->getRef()->hasMethod($vName))
					$rMethod = $ref->method($vName);

				$items = $prop["item"];
				if(array_key_exists("items", $prop))
					$items = $prop["items"];

				/**
				* Allow referencing another field
				*	Example: You have `password` and `confirm_password`
				*				you would have to use Strukt\Validator.equalTo
				*				on `confirm_password`field annotion in the @Form
				*				you'd indicate an annotaion validator like this:
				*					@EqualTo(.password)
				*/
				if(is_string($items)){

					$isArgExpected = false;
					if(preg_match("/^\./",$items)){

						$isArgExpected = true;
						$items = $this->get(trim($items, "."));
					}
				}

				$withArg = false;
				if(!empty($items) || $isArgExpected)
					$withArg = true;

				if($withArg)
					$rMethod->invoke($items);
				else
					$rMethod->invoke();
			}

			$message = $service->getMessage();
			$messages[$key] = $message;
		}

		foreach($messages as $field=>$props)
      		if(!array_product(array_values($props)))
          			return array(

          				"success"=>false, 
          				"fields"=>$messages,
          				"message"=>"Validation error!"
          			);

    	return array(

			"success"=>true, 
			"message"=>"None"
    	);
	}
}