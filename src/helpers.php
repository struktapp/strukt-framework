<?php

helper("framework");

if(helper_add("repos")){

	function repos(string $type = null){

		if(is_null($type))
			return \Strukt\Package\Repos::available();

		return \Strukt\Package\Repos::packages($type);		
	}
}

if(helper_add("ddd")){

	function ddd(mixed $message){

		if(php_sapi_name() == "cli"){

			if(is_array($message))
				$message = json($message)->pp();

			if(!is_array($message) && !is_string($message)){

				ob_start();
				var_export($message);
				$message = ob_get_contents();
				ob_end_clean();
			}

			if(is_string($message))
				print_r(color("yellow", $message));
		}
	}
}

if(helper_add("form")){

	function form(string $which, \Strukt\Http\Request $request){

		$nr = reg()->get("nr");
		$aliases = $nr->get("modules")->keys();

		$forms = arr(array_flip($aliases))->each(function($a, $v) use($nr){

			$ls = arr($nr->get(str($a)->concat(".frm"))->keys())->each(function($k, $v) use($a){

				return str($a)->concat(".frm.")->concat($v)->yield();
			});

			$ls = arr(array_flip($ls->yield()))->each(function($k, $v){

				return str(arr(str($k)->split("."))->last()->yield())->toSnake()->yield();
			});

			return array_flip($ls->yield());
		});

		$forms = $forms->level();

		return core($forms[$which], [$request]);
	}
}

if(helper_add("validator")){

	function validator(string $type, mixed $value){

		$validator = new App\Validator($value);
		$validator = ref($validator)->method(lcfirst(str($type)->toCamel()->yield()))->invoke();
		$messages = $validator->getMessage();

		return reset($messages);
	}
}

if(helper_add("request")){

	function request(array $args = [], array $headers = null){

		$request = new \Strukt\Http\Request($args);
		if(notnull($headers))
			$request->headers->add($headers);

		return $request; 
	}
}

if(helper_add("core")){

	function core(string $alias, ?array $args = null){

		return event("provider.core")->apply($alias, $args)->exec();
	}
}

if(helper_add("routes")){

	function route(string $path){

		return new class($path){

			private $matcher;
			private $url;
			private $path;

			public function __construct(string $path){

				$this->path = $path;
				$this->matcher = matcher();
				$this->url = $this->matcher->which($path);
			}

			public function post(...$args){

				return $this->path("POST", ...$args);
			}

			public function get(...$args){

				return $this->path("GET", ...$args);
			}

			public function path(string $method, ...$args){

				$method = str($method)->toUpper()->yield();
				$pattern = sprintf("type:route|path:%s|action:%s", $this->url, $method);

				if(preg_match("/\{\w+:\w+\}/", $this->url)){

					$multitr = new MultipleIterator();
					$multitr->attachIterator(new ArrayIterator(str($this->path)->split("/")));
					$multitr->attachIterator(new ArrayIterator(str($this->url)->split("/")));

					foreach($multitr as $couple){

					    list($ppath, $purl) = $couple;
					    if(preg_match("/\{\w+:\w+\}/", $purl)){

					    	list($param, $type) = str($purl)->replace(["{","}"], "")->split(":");
					    	$params[$param] = $ppath;
					    }
					}

					$args = array_merge(array_values($params), $args);
				}

				return \Strukt\Cmd::exec($pattern, $args);
			}
		};
	}
}

if(helper_add("package")){

	function package(string $name, string $mode="App:Cli"){

		$repos = repos();
		if(negate(arr($repos)->contains($name)))
			raise(sprintf("Package %s does not exist!", $name));

		if(negate(in_array($mode, ["App:Cli", "App:Idx"])))
			raise("Invalid package mode!");

		return new class($repos[$name], $mode){

			private $meta;
			private $mode;

			public function __construct(string $class, string $mode){

				$this->meta = new $class;
				$this->mode = $mode;
			}

			public function get(string $which){

				$settings = $this->meta->getSettings($this->mode);

				switch ($which) {
					case 'providers':
					case 'provider':
					case 'prv': return $settings["providers"];
					case 'middlewares': 
					case 'middleware': 
					case 'mdl': return $settings["middlewares"];
					case 'commands': 
					case 'command': 
					case 'cmd': return $settings["middlewares"];
					case 'settings':
					case 'config':
					case 'cfg': return $settings;
						break;
					case 'name': return $this->meta->getName(); 
						break;
					case 'cmd:name': return $this->meta->getCmdName(); 
						break;
					case 'files': return $this->meta->getFiles(); 
						break;
					case 'modules':
					case 'mods':
					case 'mod': return $this->meta->getModules(); 
						break;
					case 'is:published':
					case 'is:pub':
					case 'pub': return $this->meta->isPublished(); 
						break;
					case 'requirements':
					case 'reqs':
					case 'req': return $this->meta->getRequirements(); 
						break;	
					default: 
						return null; 
						break;
				}
			}
		};
	}
}