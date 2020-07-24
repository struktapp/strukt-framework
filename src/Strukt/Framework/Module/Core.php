<?php

namespace Strukt\Framework\Module;

use Strukt\Contract\AbstractCore;
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
class Core extends AbstractCore{

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

		if(!$this->core()->exists("nr"))
			new Raise("[nr|Name Registry] does not exists!");

		$this->nr = $this->core()->get("nr");
	}

	public function isQualifiedAlias($alias){

		return preg_match("/[a-z]{2}\.[a-z]{3}\.\w+/", $alias);
	}

	public function getNamespace($alias_ns){

		if($this->isQualifiedAlias($alias_ns))
			return $this->nr->get($alias_ns);
		else 
			return Str::create($this->core()->get("app.name"))
				->concat("\\")
				->concat($alias_ns)
				->yield();
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

		return Ref::create($this->getNamespace($alias_ns))->noMake()->getInstance();
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

		return Ref::create($this->getNamespace($alias_ns))->makeArgs($args)->getInstance();
	}
}