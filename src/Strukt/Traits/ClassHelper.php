<?php

namespace Strukt\Traits;

use Strukt\Ref;
use Strukt\Raise;
use Strukt\Type\Str;

trait ClassHelper{

	public function getClass(string $class){

		$class_name = Str::create($class);

		if($class_name->contains("{{app}}"))
			$class_name = $class_name->replace("{{app}}", config("app.name"));

		return $class_name->yield();
	}

	public function newClass(string $class){

		$class = $this->getClass($class); 

		if(!class_exists($class))
			new Raise(sprintf("%s does not exist!", $class));

		return Ref::create($class)->noMake()->getInstance();
	}
}