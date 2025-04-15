<?php

use Strukt\Http\Request;
use Strukt\Package\Repos;
use Strukt\Contract\FacetInterface;
use Strukt\Framework\Contract\Form as AbstractForm;
use Symfony\Component\String\Inflector\EnglishInflector;
use Ramsey\Uuid\Uuid as RamseyUuid;

helper("framework");

if(helper_add("repos")){

	/**
	 * Check for strukt modules
	 * 
	 * Example:
	 * 	repos(); //all strukt modules
	 *  repos("installed");
	 *  repos("published");
	 * 
	 * @param string $type
	 * 
	 * @return array
	 */
	function repos(string $type = null):array{

		if(is_null($type))
			return Repos::available();

		return Repos::packages($type);		
	}
}

if(helper_add("form")){

	/**
	 * Example: 
	 * 	$f = form("au.frm.User", request(["username"=>"pitsolu", "password"=>"p@55w0rd"]));
	 * 	$messages = $f->validate();
	 * 
	 * @param string $which
	 * @param \Strukt\Http\Request $request
	 * 
	 * @return \Strukt\Framework\Contract\Form
	 */
	function form(string $which, Request $request):AbstractForm{

		$alias = new class($which){

			use Strukt\Traits\FacetHelper;

			private $which;

			/**
			 * @param string $which
			 */
			public function __construct(string $which){

				$this->which = $which;
			}

			/**
			 * @return string
			 */
			public function valid():string{

				$module_alias = null;
				$class_name = null;
				$facet_alias = null;
				$which = null;

				$qualified = $this->isQualifiedAlias($this->which);

				if($qualified){

					list($_, $facet_alias, $_) = str($this->which)->split(".");
					if(str($facet_alias)->equals("frm"))
						return $this->which;

					raise(sprintf("Invalid form[%s]!", $this->which));
				}

				if(negate($qualified))
					if(preg_match("/^[a-z]{2}\.\w+$/", $this->which))
						list($module_alias, $class_name) = str($this->which)->split(".");

				if(notnull($module_alias) && notnull($class_name))
					$which = str($module_alias)
						->concat(str("frm")->prepend("."))
						->concat(str($class_name)->prepend("."))
						->yield();

				if(is_null($which))
					raise(sprintf("Invalid form[%s]!", $this->which));

				return $which;
			}
		};

		return core($alias->valid(), [$request]);
	}
}

if(helper_add("validator")){

	/**
	 * Example: validator("is_len", "strukt", 5)
	 * 
	 * @param string $type - name of validator in slug case e.g is_len 
	 * @param mixed $value - your argument to validate
	 * @param ...$args - validator arguments
	 * 
	 * @return bool
	 */
	function validator(string $type, mixed $value, ...$args):bool{

		$validator = new App\Validator($value);
		$validator = ref($validator)->method(lcfirst(str($type)->toCamel()->yield()))->invoke(...$args);
		$messages = $validator->getMessage();

		return reset($messages);
	}
}

if(helper_add("request")){

	/**
	 * Example: $r = request(["username"=>"pitsolu", "password"=>"p@55w0rd"])
	 * 
	 * @param array $args
	 * @param array $headers
	 * 
	 * @return \Strukt\Http\Request
	 */
	function request(array $args = [], ?array $headers = null):Request{

		$request = new Request($args);
		if(notnull($headers))
			$request->headers->add($headers);

		return $request; 
	}
}

if(helper_add("core")){

	/**
	 * @param string $alias
	 * @param string $args
	 * 
	 * @return \Strukt\Contract\FacetInterface
	 */
	function core(string $alias, ?array $args = null):FacetInterface{

		return event("provider.core")->apply($alias, $args)->exec();
	}
}

if(helper_add("route")){

	/**
	 * Execute route
	 * 
	 * Example: 
	 * 		$r1 = route("/")->get();
	 * 		$r2 = route("/hello/pitsolu")->get(request());
	 * 		$r3 = route("/login")->post(request(["username"=>"admin", "password"=>"p@55w0rd"]));
	 * 		$r3->getContent();
	 * 
	 * @param string $path
	 * 
	 * @return object
	 */
	function route(string $path):object{

		return new class($path){

			private $matcher;
			private $url;
			private $path;

			/**
			 * @param string $path
			 */
			public function __construct(string $path){

				$this->path = $path;
				$this->matcher = matcher();
				$this->url = $this->matcher->which($path);
			}

			/**
			 * @param ...$args
			 */
			public function post(...$args){

				return $this->path("POST", ...$args);
			}

			/**
			 * @param ...$args
			 */
			public function get(...$args){

				return $this->path("GET", ...$args);
			}

			/**
			 * @param string $method
			 * @param ...$args
			 */
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

	/**
	 * Query any package metadata
	 * 
	 * Example: package("core")->get("requirements");
	 * 
	 * @param string $name - Package name e.g pkg-db
	 * @param string $mode - App:Cli or App:Idx
	 * 
	 * @return object
	 */
	function package(string $name, string $mode="App:Cli"){

		$repos = repos();
		if(negate(arr($repos)->contains($name)))
			raise(sprintf("Package %s does not exist!", $name));

		if(negate(in_array($mode, ["App:Cli", "App:Idx"])))
			raise("Invalid package mode!");

		return new class($repos[$name], $mode){

			private $meta;
			private $mode;

			/**
			 * @param string $class
			 * @param string $mode
			 */
			public function __construct(string $class, string $mode){

				$this->meta = new $class;
				$this->mode = $mode;
			}

			/**
			 * Query package manifest
			 * 
			 * @param string $which
			 * 
			 * Arguments for $which:
			 * 	Providers    - providers|provider|prv
			 * 	Middlewares  - middlewares|middleware|mdl
			 *  Commands     - commands|command|cmd
			 *  Settings     - settings|config|cfg
			 *  Package Name - name
			 *  Command Name - cmd:name
			 *  Files        - files
			 *  Modules      - modules|mods|mod
			 *  Published    - is:published|is:pub|pub
			 *  Requirements - requirements|reqs|req
			 * 
			 * @return array|string|null
			 */
			public function get(string $which):array|string|null{

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

if(helper_add("uuid")){

	/**
	 * @param int $version - UUID default version 4
	 * @param array $options
	 */
	function uuid(int $version=4, array $options = []){

		if(negate(class_exists(RamseyUuid::class)))
			raise("fn[uuid] requires ramsey/uuid:^4.7!");

		return new class($version, $options){

			/**
			 * @param int $version
			 * @param array $options
			 */
			public function __construct(int $version, array $options){

				$this->uuid = Strukt\Ref::create(RamseyUuid::class)
								->noMake()
								->method(str("uuid")->concat($version))
								->invoke(...$options);
			}

			public function yield(){

				return $this->uuid->toString();
			}
		};
	}
}

if(helper_add("singular")){

	/**
	 * @param string $actor
	 * 
	 * @return string
	 */
	function singular(string $actor):string{

		$inflector = new EnglishInflector();
		$actor = str(arr($inflector->singularize($actor))->pop());

		return $actor->yield();
	}
}

if(helper_add("plural")){

	/**
	 * @param string $actor
	 * 
	 * @return string
	 */
	function plural(string $actor):string{

		$inflector = new EnglishInflector();
		$actor = str(arr($inflector->pluralize($actor))->pop());

		return $actor->yield();
	}
}