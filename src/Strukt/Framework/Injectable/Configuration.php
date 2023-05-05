<?php

namespace Strukt\Framework\Injectable;

use Strukt\Builder\Collection as CollectionBuilder;
use Strukt\Framework\App as FrameworkApp;

class Configuration implements \Strukt\Contract\Injectable{

	private $packages;
	private $injectables;

	public function __construct(\ReflectionClass $rclass){

		$this->packages = FrameworkApp::packages("published");

		$parser = new \Strukt\Annotation\Parser\Basic($rclass);
		$notes = $parser->getAnnotations();

		$refCls = \Strukt\Ref::create($notes["class_name"]);

		foreach($notes["methods"] as $method_name=>$note){

			$method = $refCls->noMake()->method($method_name)->getClosure();
			$key = sprintf("@inject.%s", $note["Inject"]["item"]);
			$inj[$note["Package"]["item"]][$key] = $method;
		}

		$this->injectables = $inj;
	}

	public function getConfigs(){

		$cfg = [];
		foreach($this->packages as $package)
			if(array_key_exists($package, $this->injectables))
				$cfg = array_merge($cfg, $this->injectables[$package]);
			
		return $cfg;
	}
}