<?php

namespace Strukt;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class Entity{

	public function __construct(...$args){

		$idx = 0;
		foreach(get_object_vars($this) as $var=>$val){
			$this->$var = $args[$idx];
			$idx++;
		}
	}
}