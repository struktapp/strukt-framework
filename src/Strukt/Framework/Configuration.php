<?php

namespace Strukt\Framework;

use Strukt\Framework\Injectable\Configuration as InjectableCfg;
use Strukt\Annotation\Parser\Basic as BasicNotesParser;
use Strukt\Traits\ClassHelper;
use Strukt\Package\Repos;

class Configuration{

	use ClassHelper;

	private $settings = null;
	private $ignore = [];
	private $facet = [];
	private $injectables;

	public function __construct(array $options = []){

		/**
		* Will allow ignoring @Required annotation in providers and middlewares
		* Use ONLY for in App:Cli - Currently only works for ./xhttp file
		* currently only supports '@require'
		*/
		if(array_key_exists("ignore", $options))
			$this->ignore = $options["ignore"];

		$app_type = config("app.type");
		$published = Repos::packages("published");
		$packages = Repos::available();

		$providers = [];
		$middlewares = [];
		$commands = [];
		foreach($packages as $name=>$cls){

			if(class_exists($cls) && in_array($name, $published)){

				$config = $this->newClass($cls)->getSettings($app_type);

				if(array_key_exists("providers", $config))
					foreach($config["providers"] as $provider)
						if(class_exists($provider))
							$providers[] = $provider;

				if(array_key_exists("middlewares", $config))
					foreach($config["middlewares"] as $middleware)
						if(class_exists($middleware))
							$middlewares[] = $middleware;

				if(array_key_exists("commands", $config))
					foreach($config["commands"] as $command)
						if(class_exists($command = $this->getClass($command)))
							$commands[] = $command;
			}
		}

		$config = [];//reset config
		if(!empty($providers))
			$config["providers"] = $providers;

		if(!empty($middlewares))
			$config["middlewares"] = $middlewares;

		if(!empty($commands))
			$config["commands"] = $commands;

		$injectables = new InjectableCfg(new \ReflectionClass(\App\Injectable::class));

		$facet = [];
		$this->settings = arr([

			"middlewares"=>null,
			"providers"=>null

		])->each(function($key, $val) use($injectables, $config, &$facet){

			$settings = [];
			foreach($config[$key] as $setting){

				$parser = new BasicNotesParser(new \ReflectionClass($setting));
				$notes = $parser->getAnnotations();

				$name = $notes["class"]["Name"]["item"];
				if(!empty(config($key)))
					if(in_array($name, config($key)))
						$settings[] = $setting;
					
				if(array_key_exists("Required", $notes["class"]))
					$settings[] = $setting;

				if(array_key_exists("Requires", $notes["class"]) && 
					!in_array("@require", $this->ignore)){

					$requires = $notes["class"]["Requires"]["item"];
					if(!reg()->exists($requires))
						raise(sprintf("%s:[%s] requires registry:item[%s]!", 
											ucfirst(trim($key, "s")),
											$name, 
											$requires));
				}

				if(array_key_exists("Inject", $notes["class"])){

					$inj_name = $notes["class"]["Inject"]["item"];
					$inj_keys = array_keys($injectables->getConfigs());

					if(!in_array(sprintf("@inject.%s", $inj_name), $inj_keys))
						raise(sprintf("%s:[%s] requires provider:[%s]!", 
											ucfirst(trim($key, "s")),
											$name, 
											$inj_name));
				}

				$facet[$key][] = $name;
			}

			return $settings;
		});

		$this->injectables = $injectables;
		$this->facet = $facet;
	}

	public function getInjectables(){

		return $this->injectables;
	}

	/**
	 * @param string $key
	 * 
	 * - commands
	 * - providers
	 * - middlewares
	 * 
	 * @param array $settings
	 */
	public function set(string $key, array $settings){

		$this->settings[$key] = $settings;
	}

	/**
	 * Configuration.get($key) Will filter non-compulsory middlewares 
	 * and providers using ./cfg/app.ini
	 * 
	 * @param string $key
	 *
	 * Options:
	 * - commands
	 * - providers
	 * - middlewares
	 */
	public function get(string $key){

		if(in_array($key, ["providers", "middlewares", "commands"]))
			return $this->settings[$key];

		return null;
	}

	public function getAliases(){

		return $this->facet;
	}
}