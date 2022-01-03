<?php

namespace Strukt\Framework;

use Strukt\Builder\Collection as CollectionBuilder;
use Strukt\Framework\App as FrameworkApp;

class Injectable{

	private $packages;
	private $injectables;

	public function __construct(array $injectables){

		$this->packages = FrameworkApp::packages("published");

		$this->injectables = $injectables;
	}

	public function getConfigs(){

		$cfg = [];
		foreach($this->packages as $package)
			if(array_key_exists($package, $this->injectables))
				$cfg = array_merge($cfg, $this->injectables[$package]);
			
		return $cfg;
	}
}