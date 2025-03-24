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

	/**
	 * @return void
	 */
	public function register():void{		

		event("provider.validator", function(){

			return new class{

			    public function getNew(?string $value = null) {

			    	if(class_exists(\App\Validator::class))	        
			        	return new \App\Validator($value);

			        return null;
			    }
	   		};
	   	});
	}
}
