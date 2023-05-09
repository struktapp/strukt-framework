<?php

namespace Strukt\Provider;

use Strukt\Event;
use Strukt\Contract\Provider\AbstractProvider;
use Strukt\Contract\Provider\ProviderInterface;
use App\Validator\Extra as VExtra;

/**
* @Name(valid)
* @Required()
*/
class Validator extends AbstractProvider implements ProviderInterface{

	public function __construct(){

		//
	}

	public function register(){
		
		$this->core()->set("strukt.service.validatorExtra", new class{

		    public function getNew(string $value = null) {
				
				if(class_exists(VExtra::class))	        
		        	return new VExtra($value);

		        return null;
		    }
   		});			

		$this->core()->set("strukt.service.validator", new class{

		    public function getNew(string $value = null) {
		        
		        return new \Strukt\Validator($value);
		    }
   		});
	}
}
