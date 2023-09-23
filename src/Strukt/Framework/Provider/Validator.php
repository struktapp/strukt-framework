<?php

namespace Strukt\Framework\Provider;

use Strukt\Contract\ProviderInterface;

/**
* @Name(valid)
* @Required()
*/
class Validator implements ProviderInterface{

	public function __construct(){

		//
	}

	public function register(){		

		event("provider.validator", new class{

		    public function getNew(string $value = null) {

		    	if(class_exists(\App\Validator::class))	        
		        	return new \App\Validator($value);

		        return null;
		    }
   		});
	}
}
