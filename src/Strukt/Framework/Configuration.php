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
		$ignore = [];
		if(array_key_exists("ignore", $options))
			$this->ignore = $ignore = $options["ignore"];

		$app_type = config("app.type");
		$published = Repos::packages("published");
		$packages = Repos::available();

		$settings = [];
		$factes = arr($packages)->each(function($name, $class) use($published, $app_type, &$settings){

			if(class_exists($class) && in_array($name, $published)){

				$helper = new class(){use ClassHelper;};

				$config = $helper->newClass($class)->getSettings($app_type);

				$facet = arr(["middlewares"=>[], 
								"providers"=>[], 
								"commands"=>[]])->each(function($facet, $value) use($config, $helper){

					if(arr(array_keys($config))->has($facet))
						return arr($config[$facet])->each(function($key, $facet_class) use($helper){

							$facet_class = $helper->getClass($facet_class);
							if(class_exists($facet_class))
								return $facet_class;

							return null;
						})->yield();
				})->yield();

				$settings = array_merge($settings, $facet);
			}
		});

		$this->settings = $settings;

		print_r($this->settings);

		// $this->settings = $settings->yield();
		// $this->injectables = $injectables;
		// $this->facet = $facet;
	}

	public function getInjectables(){

		return new InjectableCfg(new \ReflectionClass(\App\Injectable::class));
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

		// print_r($this->settings);exit;

		if(in_array($key, ["providers", "middlewares", "commands"]))
			return $this->settings[$key];

		return null;
	}

	public function getAliases(){

		return $this->facet;
	}
}