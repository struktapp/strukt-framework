<?php

namespace Strukt\Framework;

use Strukt\Framework\App as FrameworkApp;
use Strukt\Framework\Service\Configuration\Injectable as InjectableCfg;
use Strukt\Annotation\Parser\Basic as BasicNotesParser;

class Configuration{

	private $packages;
	private $settings;
	private $ignore = [];

	public function __construct(array $options = []){

		/**
		* Will allow ignoring @Required annotation in providers and middlewares
		* Use ONLY for in App:Cli - Currently only works for ./xhttp file
		* currently only supports '@require'
		*/
		if(array_key_exists("ignore", $options))
			$this->ignore = $options["ignore"];

		$this->packages = FrameworkApp::getRepo();
		$this->settings = $this->getSetup();
	}

	public function getInjectables(){

		return new InjectableCfg(new \ReflectionClass(\App\Injectable::class));
	}

	public function getSetup(){

		$providers = [];
		$middlewares = [];
		$commands = [];

		$app_type = FrameworkApp::getType();

		$published = FrameworkApp::packages("published");

		foreach($this->packages as $name=>$cls){

			if(class_exists($cls) && in_array($name, $published)){

				$pkg = FrameworkApp::newCls($cls);

				$settings = $pkg->getSettings($app_type);

				// $this->pkg_ls[$name] = $settings; 

				if(array_key_exists("providers", $settings))
					foreach($settings["providers"] as $provider)
						if(class_exists($provider))
							$providers[] = $provider;

				if(array_key_exists("middlewares", $settings))
					foreach($settings["middlewares"] as $middleware)
						if(class_exists($middleware))
							$middlewares[] = $middleware;

				if(array_key_exists("commands", $settings))
					foreach($settings["commands"] as $command)
						if(class_exists($command = \Strukt\Framework\App::getCls($command)))
							$commands[] = $command;
			}
		}

		if(!empty($providers))
			$cfgs["providers"] = $providers;

		if(!empty($middlewares))
			$cfgs["middlewares"] = $middlewares;

		if(!empty($commands))
			$cfgs["commands"] = $commands;

		return $cfgs;
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

		if(in_array($key, ["middlewares", "providers"])){

			$rel_app_ini = \Strukt\Env::get("rel_app_ini");
			if(\Strukt\Fs::isFile($rel_app_ini))
				$appIni = parse_ini_file($rel_app_ini);

			$settings = [];
			foreach($this->settings[$key] as $facet){

				// print_r($facet."\n");
				$parser = new BasicNotesParser(new \ReflectionClass($facet));
				$notes = $parser->getAnnotations();

				$name = $notes["class"]["Name"]["item"];
				if(!empty($appIni[$key]))
					if(in_array($name, $appIni[$key]))
						$settings[] = $facet;
					
				if(array_key_exists("Required", $notes["class"]))
					$settings[] = $facet;

				if(array_key_exists("Requires", $notes["class"]) && 
					!in_array("@require", $this->ignore)){

					$requires = $notes["class"]["Requires"]["item"];
					if(!\Strukt\Reg::exists($requires))
						new \Strukt\Raise(sprintf("%s:[%s] requires registry:item[%s]!", 
											ucfirst(trim($key, "s")),
											$name, 
											$requires));
				}

				if(array_key_exists("Inject", $notes["class"])){

					$inj_name = $notes["class"]["Inject"]["item"];
					$inj_keys = array_keys($this->getInjectables()->getConfigs());

					if(!in_array(sprintf("@inject.%s", $inj_name), $inj_keys))
						new \Strukt\Raise(sprintf("%s:[%s] requires provider:[%s]!", 
											ucfirst(trim($key, "s")),
											$name, 
											$inj_name));
				}
			}

			return $settings;
		}
		
		if(in_array($key, ["commands"]))
			return $this->settings[$key];

		return null;
	}
}