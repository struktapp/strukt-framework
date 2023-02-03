<?php

namespace Strukt\Framework\Provider;

use Strukt\Event;
use Strukt\Contract\Provider\AbstractProvider;
use Strukt\Contract\Provider\ProviderInterface;

/**
* @Name(valid)
* @Require(must)
*/
class Validator extends AbstractProvider implements ProviderInterface{

	public function __construct(){

		//
	}

	public function register(){

		$this->core()->set("strukt.service.validator", new class{

		    public function getNew(string $value = null) {
		        
		        return new \Strukt\Validator($value);
		    }
   		});
	}
}
