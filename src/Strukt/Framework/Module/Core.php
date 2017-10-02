<?php

namespace Strukt\Framework\Module;

use Strukt\Core\Collection;
use Strukt\Core\Registry;

/**
* Strukt Application Module Core
*
* Enables easy access to module functionality via 
* aliasing lengthy name spaces
*
* @author Moderator <pitsolu@gmail.com>
*/
class Core{

	/**
	* Name Registry
	*
	* @var \Strukt\Core\Map
	*/
	private $nr = null;

	/**
	* Constructor
	*/
	public function __construct(){

		$this->nr = Registry::getInstance()->get("nr");
	}

	/**
	* Getter for class functionality in static class
	*
	* $alias_ns format <module>.<facet>.<class> e.g au.ctr.User
	*
	* @param string $alias_ns
	*
	* @return object
	*/
	public function get($alias_ns){

		$ns = $this->nr->get($alias_ns);

		$class = new \ReflectionClass($ns);

		return $class->newInstanceWithoutConstructor();
	}

	/**
	* Getter for class functionality in instantiated class
	*
	* $alias_ns format <module>.<facet>.<class> e.g au.ctr.User
	*
	* @param string $alias_ns
	* @param Array $args = null
	*
	* @return object
	*/
	public function getNew($alias_ns, Array $args = null){

		$ns = $this->nr->get($alias_ns);

		$class = new \ReflectionClass($ns);

		if(is_null($args))
			$newInstance = $class->newInstance();
		else
			$newInstance = $class->newInstanceArgs($args);

		return $newInstance;
	}
}