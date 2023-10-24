<?php

namespace Strukt\Framework;

use Strukt\Framework\Injectable\Configuration as InjectableCfg;
use Strukt\Framework\Injectable\Facet as InjectableFacet;
use Strukt\Annotation\Parser\Basic as BasicNotesParser;
use Strukt\Traits\ClassHelper;
use Strukt\Package\Repos;

class Configuration{

	use ClassHelper;

	private $settings = null;
	private $aliases = null;

	public function __construct(array $options = []){

		/**
		* Will allow ignoring @Required annotation in providers and middlewares
		* Use ONLY for in App:Cli - Currently only works for ./xhttp file
		* currently only supports '@require'
		*/
		// $ignore = [];
		// if(array_key_exists("ignore", $options))
			// $this->ignore = $ignore = $options["ignore"];

		$app_type = config("app.type");
		$published = Repos::packages("published");
		$packages = Repos::available();

		$settings = [];
		$aliases = [];
		arr($packages)->each(function($name, $class) use($published, $app_type, &$settings, &$aliases){

			// dd(array($name, $class));

			print_r(array());

			if(class_exists($class) && in_array($name, $published)){

				dd(array($name, $class, $published));

				$helper = new class(){use ClassHelper;};

				$config = $helper->newClass($class)->getSettings($app_type);

				$facet = arr(["middlewares"=>[], 
								"providers"=>[], 
								"commands"=>[]])->each(function($facet, $value) 
														use($config, $helper, &$aliases){

					if(arr(array_keys($config))->has($facet))
						return array_values(array_filter(arr($config[$facet])
									->each(function($key, $facet_class) use($helper, $facet, &$aliases){

							$facet_class = $helper->getClass($facet_class);
							if(class_exists($facet_class)){

								if(str($facet)->equals("commands"))
									return $facet_class;

								$inj_facet = new InjectableFacet(new \ReflectionClass($facet_class));
								$facet_configs = $inj_facet->getConfigs();
								
								if(!is_null($facet_configs)){

									$aliases[$facet][] = $facet_configs["config"]["name"];

									return $facet_class;
								}
							}
						})->yield()));
				})->yield();

				$settings = array_merge($settings, $facet);
			}
		});

		$this->settings = $settings;
		$this->aliases = $aliases;
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

		if(in_array($key, ["providers", "middlewares", "commands"]))
			return $this->settings[$key];

		return null;
	}

	public function getAliases(){

		return $this->aliases;
	}
}