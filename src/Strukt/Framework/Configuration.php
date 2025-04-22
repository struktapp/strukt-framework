<?php

namespace Strukt\Framework;

use Strukt\Framework\Injectable\Configuration as InjectableCfg;
use Strukt\Framework\Injectable\Facet as InjectableFacet;
use Strukt\Annotation\Parser\Basic as BasicNotesParser;
use Strukt\Traits\ClassHelper;
use Strukt\Traits\InjectablesHelper;
use Strukt\Package\Repos;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class Configuration{

	use ClassHelper;

	private $settings = null;
	private $aliases = null;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = []){

		/**
		* @todo Will allow ignoring @Required annotation in providers and middlewares
		* Use ONLY for in App:Cli - Currently only works for ./xhttp file
		* currently only supports '@require'
		*/
		// $ignore = [];
		// if(array_key_exists("ignore", $options))
		//		$this->ignore = $ignore = $options["ignore"];

		$settings = [];
		$app_type = config("app.type");

		//app.json exists
		if(cache("app")->empty()){

			$settings = $this->create($app_type);
			cache("app")->put($app_type, $settings)->save();
		}

		//app.json doesn't exist
		if(empty($settings)){			

			if(!cache("app")->exists($app_type)){

				$settings = $this->create($app_type);
				cache("app")->put($app_type, $settings)->save();
			}

			if(empty($settings))
				$settings = cache("app")->get($app_type);
		}

		$this->settings = $settings;
	}

	/**
	 * @param string $app_type
	 * 
	 * @return array
	 */
	public static function create(string $app_type){

		$published = Repos::packages("published");
		$packages = Repos::available();
		
		$aliases = [];
		$commands = [];
		$settings = [];

		arr($packages)->each(function($name, $class) use($published, 
															$app_type, 
															&$settings, 
															&$aliases, 
															&$commands){

			if(class_exists($class) && in_array($name, $published)){

				$helper = new class(){use ClassHelper, InjectablesHelper;};

				$package = $helper->newClass($class);
				$config	= $package->getSettings($app_type);

				if(array_key_exists("commands", $config))
					$commands = array_merge($commands, $config["commands"]);

				$facet = arr(["middlewares"=>[], "providers"=>[]]);
				$facet = $facet->each(function($facet, $value) use($config, $helper, &$aliases){

					if(arr(array_keys($config))->has($facet)){
						
						$facet_settings = arr($config[$facet]);
						$facet_settings->each(function($key, $facet_class) use($helper, $facet, &$aliases){

							$facet_class = $helper->getClass($facet_class);
							if(class_exists($facet_class)){

								$facet_configs = $helper->resolveInjectables($facet_class);

								if(is_null(@$aliases[$facet]))
									$aliases[$facet] = [];
								
								$alias = null;
								if(!is_null($facet_configs))
									if(!in_array($facet_configs["alias"], $aliases[$facet]))
										$aliases[$facet][] = $alias = $facet_configs["alias"];

								if(!is_null($alias))
									return $facet_class;

								return null;
							}

						})->yield();

						return array_values(array_filter($facet_settings->yield()));
					}
				});

				$facet = $facet->yield();
				$settings = array_merge_recursive($settings, array_filter($facet));
			}
		});

		$settings["commands"] = arr($commands)->each(function($k, $class){

			$helper = new class(){use ClassHelper;};

			return $helper->getClass($class);

		})->yield();

		$settings["aliases"] = $aliases;

		if(!array_key_exists("providers", $settings))
			$settings["providers"] = [];

		if(!array_key_exists("middlewares", $settings))
			$settings["middlewares"] = [];

		return $settings;
	}

	/**
	 * @return \Strukt\Framework\Injectable\Configuration
	 */
	public function getInjectables():InjectableCfg{

		return new InjectableCfg(new \ReflectionClass(\App\Injectable::class));
	}

	/**
	 * Configuration.get($key) Will filter non-compulsory middlewares 
	 * and providers using ./cfg/app.ini
	 * 
	 * Options:
	 * - commands
	 * - providers
	 * - middlewares
	 * 
	 * @param string $key
	 *
	 * @return array|null
	 */
	public function get(string $key):array|null{

		if(in_array($key, ["providers", "middlewares", "commands"]))
			return is_array($this->settings)?$this->settings[$key]:$this->settings?->get($key);

		return null;
	}

	/**
	 * @return array
	 */
	public function getAliases():array{

		return $this->settings->get("aliases");
	}
}