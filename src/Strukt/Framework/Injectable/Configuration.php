<?php

namespace Strukt\Framework\Injectable;

use Strukt\Package\Repos;
use Strukt\Annotation\Parser\Basic as BasicNotesParser;
use \Strukt\Framework\Contract\Injectable as InjectableInterface;

class Configuration implements InjectableInterface{

	private $packages;
	private $injectables;

	/**
	 * @param \ReflectionClass $rclass
	 */
	public function __construct(\ReflectionClass $rclass){

		$this->published = Repos::packages("published");

		$parser = new BasicNotesParser($rclass);
		$notes = $parser->getNotes();

		$refClass = \Strukt\Ref::create($notes["class_name"]);

		foreach($notes["methods"] as $method_name=>$note){

			$method = $refClass->noMake()->method($method_name)->getClosure();
			// $key = sprintf("@inject.%s",$note["Inject"]["item"]);
			$key = $note["Inject"]["item"];
			$inj[$note["Package"]["item"]][$key] = $method;
		}

		$this->injectables = $inj;
	}

	/**
	 * @return array
	 */
	public function getConfigs():array|null{

		$cfg = [];
		foreach($this->published as $package)
			if(array_key_exists($package, $this->injectables))
				$cfg = array_merge($cfg, $this->injectables[$package]);
			
		return $cfg;
	}
}