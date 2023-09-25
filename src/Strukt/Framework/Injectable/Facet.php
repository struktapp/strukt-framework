<?php

namespace Strukt\Framework\Injectable;

use Strukt\Annotation\Parser\Basic as BasicNotesParser;
use Strukt\Framework\Injectable\Configuration as InjectableCfg;
use App\Injectable as InjectableApp;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\ProviderInterface;

class Facet implements \Strukt\Framework\Contract\Injectable{

	private $notes = null;

	public function __construct(\ReflectionClass $rclass){

		$parser = new BasicNotesParser($rclass);
		$notes = $parser->getAnnotations();

		$injectables = new InjectableCfg(new \ReflectionClass(InjectableApp::class));

		$name = $notes["class"]["Name"]["item"];
		$settings["name"] = $name;

		$interfaces = arr($rclass->getInterfaceNames());
		if($interfaces->has(MiddlewareInterface::class))
			$facet = arr(config("app.middlewares")??[]);

		if($interfaces->has(ProviderInterface::class))
			$facet = arr(config("app.providers")??[]);
		
		if($facet->has($name)){
				
			$settings["is_required"] = false;
			if(array_key_exists("Required", $notes["class"]))
				$settings["is_required"] = true;

			if(array_key_exists("Requires", $notes["class"])){

				$requires = $notes["class"]["Requires"]["item"];
				$settings["requires"][] = $requires;
				// if(!reg()->exists($requires))
				// 	raise(sprintf("%s:[%s] requires registry:item[%s]!", 
				// 						ucfirst(trim($key, "s")),
				// 						$name, 
				// 						$requires));
			}

			if(array_key_exists("Inject", $notes["class"])){

				$inj_name = $notes["class"]["Inject"]["item"];
				$inj_keys = array_keys($injectables->getConfigs());

				if(!in_array($inj_name, $inj_keys))
					raise(sprintf("%s:[%s] requires provider:[%s]!", 
										ucfirst(trim($key, "s")),
										$name, 
										$inj_name));
			}

			// $facet[$key][] = $name;

			$this->notes = array(

				"class"=>$rclass->getName(),
				"config"=>$settings
			);
		}
	}

	public function getConfigs(){

		return $this->notes;
	}
}