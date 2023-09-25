<?php

namespace Strukt\Framework;

use Strukt\Raise;
use Strukt\Ref;
use Strukt\Type\Str;

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
	* Constructor
	*/
	public function __construct(){

		if(!reg()->exists("nr"))
			new Raise("[nr|Name Registry] does not exists!");
	}

	public function isQualifiedAlias($alias){

		return preg_match("/[a-z]{2}\.[a-z]{3}\.\w+/", $alias);
	}

	public function getNamespace($alias_ns){

		if($this->isQualifiedAlias($alias_ns))
			return reg(sprintf("nr.%s", $alias_ns));
		else 
			return Str::create(config("app.name"))
				->concat("\\")
				->concat($alias_ns)
				->yield();
	}

	/**
	* Getter for class functionality in instantiated class
	*
	* $alias_ns format <module>.<facet>.<class> e.g au.ctr.User
	*
	* @param string $alias_ns
	* @param array $args = null
	*
	* @return object
	*/
	protected function getNew($alias_ns, array $args = null){

		return Ref::create($this->getNamespace($alias_ns))->makeArgs($args)->getInstance();
	}

	/**
	* Getter for class functionality in static class
	*
	* $alias_ns format <module>.<facet>.<class> e.g au.ctr.User
	*
	* @param string $alias_ns
	* @param array $args = null
	*
	* @return object
	*/
	protected function get(string $alias_ns, array $args = null){

		if(!is_null($args))
			return $this->getNew($alias_ns, $args);

		return Ref::create($this->getNamespace($alias_ns))->noMake()->getInstance();
	}
}