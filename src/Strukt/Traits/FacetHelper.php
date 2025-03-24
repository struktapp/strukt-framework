<?php

namespace Strukt\Traits;

/**
* @author Moderator <pitsolu@gmail.com>
*/
trait FacetHelper{

	/**
	 * @param string $alias
	 * 
	 * @return bool
	 */
	public function isQualifiedAlias(string $alias):bool{

		return preg_match("/[a-z]{2}\.[a-z]{3}\.\w+/", $alias);
	}

	/**
	 * @param string $alias_ns
	 * 
	 * @return string 
	 */
	public function getNamespace(string $alias_ns):string{

		if($this->isQualifiedAlias($alias_ns))
			return reg(sprintf("nr.%s", $alias_ns));
		
		return str(config("app.name"))
			->concat("\\")
			->concat($alias_ns)
			->yield();
	}
}