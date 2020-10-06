<?php

namespace Strukt\Framework;

use Cobaia\Doctrine\MonologSQLLogger;
use Strukt\Core\Map;
use Strukt\Http\Session;
use Strukt\Builder\Collection as CollectionBuilder;
use Strukt\Env;
use Strukt\Framework\App;

class Injectable{

	private $packages;
	private $map;
	private $injectables;

	public function __construct(array $packages, array $map, array $injectables){

		$packages[] = "base";

		$this->packages = $packages;

		$this->map = $map;

		$this->injectables = $injectables;
	}

	public function getId($id){

		return $this->map[$id];
	}

	public function getConfigs(){

		$cnf = [];
		foreach($this->packages as $package)
			if(array_key_exists($package, $this->injectables))
				$cnf = array_merge($cnf, $this->injectables[$package]);

		return $cnf;
	}
}