<?php

namespace Strukt\Traits;

use Strukt\Ref;
use Strukt\Raise;
use Strukt\Type\Str;

/**
* @author Moderator <pitsolu@gmail.com>
*/
trait ClassHelper{

	/**
	 * @param string $class
	 * 
	 * @return string
	 */
	public function getClass(string $class):string{

		$class_name = Str::create($class);

		if($class_name->contains("{{app}}"))
			$class_name = $class_name->replace("{{app}}", config("app.name"));

		return $class_name->yield();
	}

	/**
	 * @param string $class
	 * 
	 * @return object
	 */
	public function newClass(string $class):object{

		$class = $this->getClass($class); 

		if(!class_exists($class))
			new Raise(sprintf("%s does not exist!", $class));

		return Ref::create($class)->noMake()->getInstance();
	}
}