<?php

namespace Strukt\Contract;

use Strukt\Exception\KeyNotFoundException;
use Strukt\Contract\AbstractCore;

abstract class AbstractService extends AbstractCore{

	protected function get($alias, Array $args = null){

		$core = $this->core()->get("core");

		if(!empty($args))
			return $core->getNew($alias, $args);

		return $core->get($alias);
	}

	protected function getNs($alias_ns){

		$core = $this->core()->get("core");

		return $core->getNamespace($alias_ns);
	}

	protected function da(){

		if(!$this->core()->exists("app.da"))
			throw new KeyNotFoundException("app.da|Doctrine Adapter");

		return $this->core()->get("app.da");
	}

	protected function em(){

		if(!$this->core()->exists("app.em"))
			throw new KeyNotFoundException("app.em|Entity Manager");

		return $this->core()->get("app.em");
	}
}