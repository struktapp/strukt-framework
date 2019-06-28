<?php

namespace Strukt\Framework\Provider;

use Strukt\Event\Event;
use Strukt\Contract\AbstractProvider;
use Strukt\Contract\ProviderInterface;

class Validator extends AbstractProvider implements ProviderInterface{

	public function register(){

		$this->core()->set("app.service.validator", new class{

		    public function getNew(string $value) {
		        
		        return new \Strukt\Validator($value);
		    }
   		});
	}
}
