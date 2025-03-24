<?php

namespace Strukt\Framework\Injectable;

use Strukt\Annotation\Parser\Basic as BasicNotesParser;
use Strukt\Framework\Contract\Injectable as InjectableInterface;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Validator implements InjectableInterface{

	private $notes;

	/**
	 * @param \ReflectionClass $rclass
	 */
	public function __construct(\ReflectionClass $rclass){

		$parser = new BasicNotesParser($rclass);
		$this->notes = $parser->getAnnotations();
	}

	/**
	 * @return array|null
	 */
	public function getConfigs():array|null{

		return $this->notes["properties"];
	}
}