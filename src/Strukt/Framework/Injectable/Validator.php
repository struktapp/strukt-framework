<?php

namespace Strukt\Framework\Injectable;

use Strukt\Annotation\Parser\Basic;

class Validator implements \Strukt\Contract\Injectable{

	private $notes;

	public function __construct(\ReflectionClass $rclass){

		$parser = new \Strukt\Annotation\Parser\Basic($rclass);
		$this->notes = $parser->getAnnotations();
	}

	public function getConfigs(){

		return $this->notes["properties"];
	}
}