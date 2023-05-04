<?php

namespace Strukt\Framework\Service\Router;

use Strukt\Annotation\Parser\Basic;

class Injectable implements \Strukt\Contract\Injectable{

	private $notes = [];

	public function __construct(\ReflectionClass $rclass){

		$parser = new Basic($rclass);
		$notes = $parser->getAnnotations();

		$class_name = $notes["class_name"];
		unset($notes["class_name"]);

		foreach($notes as $note){

			if(is_null($note) || empty($note))
				continue;

			foreach($note as $method_name=>$method_items){

				if(is_null($method_items))
					continue;

				if(array_key_exists("Method", $method_items)){

					$form = "";
					if(array_key_exists("Form", $method_items))
						$form = $method_items["Form"]["item"];

					$middlewares = "";
					if(array_key_exists("Middlewares", $method_items)){

						$key = "item";
						if(array_key_exists("items", $method_items["Middlewares"]))
							$key = "items";

						$middlewares = $method_items["Middlewares"][$key];
					}

					$name = "";
					if(array_key_exists("Permission", $method_items))
						$name = $method_items["Permission"]["item"];

					if(empty($name))
						if(array_key_exists("Auth", $method_items))
							$name = "strukt:auth";

					$this->notes[] = array(

						"http.method" => $method_items["Method"]["item"],
						"route.path" => $method_items["Route"]["item"],
						"route.perm" => $name,
						"route.form" => $form,
						"route.middlewares"=>$middlewares,
						"ref.class" => $class_name,
						"ref.method" => $method_name,
					);
				}
			}
		}
	}

	public function getConfigs(){
	
		return $this->notes;
	}
}