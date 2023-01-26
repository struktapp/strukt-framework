<?php

namespace Strukt\Framework\Service\Validator;

use Strukt\Annotation\Parser\Basic;

class Injectable implements \Strukt\Contract\Injectable{

	private $notes;

	public function __construct(\ReflectionClass $rclass){

		$parser = new \Strukt\Annotation\Parser\Basic($rclass);
		$this->notes = $parser->getAnnotations();
	}

	public function getConfigs(){

		return $this->notes["properties"];
	}
}