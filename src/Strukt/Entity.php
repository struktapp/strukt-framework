<?php

namespace Strukt;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class Entity{

	public function __construct(...$args){

		$idx = 0;
		foreach(get_class_vars(get_called_class()) as $var=>$val)
			$this->$var = $args[$idx++];
	}
}