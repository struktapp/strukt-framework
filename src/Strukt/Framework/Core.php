<?php

namespace Strukt\Framework;

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
	* Getter for class functionality in static class
	*
	* $alias_ns format <module>.<facet>.<class> e.g au.ctr.User
	*
	* @param string $alias_ns
	* @param array $args = null
	*
	* @return object
	*/
	protected function get(string $alias_ns, ?array $args = null):object{

		return event("provider.core")->apply($alias_ns, $args)->exec();
	}
}