<?php

namespace Strukt\Traits;

trait FacetHelper{

	public function isQualifiedAlias(string $alias){

		return preg_match("/[a-z]{2}\.[a-z]{3}\.\w+/", $alias);
	}

	public function getNamespace(string $alias_ns){

		if($this->isQualifiedAlias($alias_ns))
			return reg(sprintf("nr.%s", $alias_ns));
		
		return str(config("app.name"))
			->concat("\\")
			->concat($alias_ns)
			->yield();
	}
}