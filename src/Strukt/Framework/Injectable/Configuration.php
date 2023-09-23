<?php

namespace Strukt\Framework\Injectable;

use Strukt\Package\Repos;
use Strukt\Annotation\Parser\Basic as BasicNotesParser;

class Configuration implements \Strukt\Framework\Contract\Injectable{

	private $packages;
	private $injectables;

	public function __construct(\ReflectionClass $rclass){

		$this->published = Repos::packages("published");

		$parser = new BasicNotesParser($rclass);
		$notes = $parser->getAnnotations();

		$refClass = \Strukt\Ref::create($notes["class_name"]);

		foreach($notes["methods"] as $method_name=>$note){

			$method = $refClass->noMake()->method($method_name)->getClosure();
			$key = sprintf("@inject.%s", $note["Inject"]["item"]);
			$inj[$note["Package"]["item"]][$key] = $method;
		}

		$this->injectables = $inj;
	}

	public function getConfigs(){

		$cfg = [];
		foreach($this->published as $package)
			if(array_key_exists($package, $this->injectables))
				$cfg = array_merge($cfg, $this->injectables[$package]);
			
		return $cfg;
	}
}