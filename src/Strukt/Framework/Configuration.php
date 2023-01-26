<?php

namespace Strukt\Framework;

use Strukt\Framework\App as FrameworkApp;
use Strukt\Framework\Service\Configuration\Injectable as InjectableCfg;

class Configuration{

	public function __construct(){

		$this->packages = FrameworkApp::getRepo();
		$this->settings = $this->getSetup();
	}

	public function getInjectables(){

		return new InjectableCfg(new \ReflectionClass(new \App\Injectable()));
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
						if(class_exists($command))
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
	 * @param string $key
	 * 
	 * - commands
	 * - providers
	 * - middlewares
	 */
	public function get(string $key){

		if(array_key_exists($key, $this->settings))
			return $this->settings[$key];

		return null;
	}
}