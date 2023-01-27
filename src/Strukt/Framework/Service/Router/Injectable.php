<?php

// namespace App\Service\Router;
namespace Strukt\Framework\Service\Router;

use Strukt\Annotation\Parser\Basic;

class Injectable implements \Strukt\Contract\Injectable{

	private $notes = [];

	public function __construct(\ReflectionClass $rclass){

		$parser = new Basic($rclass);
		$notes = $parser->getAnnotations();

		foreach($notes as $note){

			foreach($note as $method_name=>$method_items){

				if(array_key_exists("Method", $method_items)){

					$form = "";
					if(array_key_exists("Form", $method_items))
						$form = $method_items["Form"]["item"];

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
						"ref.class" => $notes["class_name"],
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