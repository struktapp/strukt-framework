<?php

namespace Strukt\Framework;

use Strukt\Env;
use Strukt\Core\Map;
use Strukt\Http\Session;
use Strukt\Builder\Collection as CollectionBuilder;
use Strukt\Framework\App as FrameworkApp;

class Injectable{

	private $map;
	private $packages;
	private $injectables;

	public function __construct(array $map, array $injectables){

		$this->map = $map;

		$this->packages = FrameworkApp::packages("published");

		$this->injectables = $injectables;
	}

	public function getId($id){

		return $this->map[$id];
	}

	public function getConfigs(){

		$cfg = [];
		foreach($this->packages as $package)
			if(array_key_exists($package, $this->injectables))
				$cfg = array_merge($cfg, $this->injectables[$package]);
			
		return $cfg;
	}
}