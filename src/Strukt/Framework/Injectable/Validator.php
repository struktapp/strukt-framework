<?php

namespace Strukt\Framework\Injectable;

use Strukt\Annotation\Parser\Basic as BasicNotesParser;

class Validator implements \Strukt\Framework\Contract\Injectable{

	private $notes;

	public function __construct(\ReflectionClass $rclass){

		$parser = new BasicNotesParser($rclass);
		$this->notes = $parser->getAnnotations();
	}

	public function getConfigs(){

		return $this->notes["properties"];
	}
}