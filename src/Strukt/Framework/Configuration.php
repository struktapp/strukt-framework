<?php

namespace Strukt\Framework;

use Strukt\Core\Collection;
use Strukt\Builder\Collection as CollectionBuilder;

class Configuration{

	public function __construct(array $settings, array $packages, string $type = "index"){

		$packages[] = "base";

		$this->packages = $packages;

		$cfg = CollectionBuilder::create()->fromAssoc($settings);

		$this->configs = $cfg->get($type);
	}

	public function get(string $key){

		$facets = array(
			
			"providers",
			"middlewares",
			"commands"
		);

		$cfgs = [];

		foreach($this->packages as $package){

			if(in_array($key, $facets)){

				$keyMap = sprintf("%s.%s", $package, $key);			

				if($this->configs->exists($keyMap)){

					$cfg = $this->configs->get($keyMap);
						
					if(is_array($cfg)){

						if($key == "commands")
							$cfgs[$package] = $cfg;
						else
							$cfgs = array_merge($cfgs, $cfg);
					}
					elseif($cfg instanceof Collection){

						foreach($cfg->getKeys() as $cfgKey)
							$cfgs[$cfgKey] = $cfg->get($cfgKey);
					}
				}
			}
		}

		return $cfgs;
	}
}