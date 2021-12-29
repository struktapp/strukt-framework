<?php

namespace Strukt\Framework;

use Strukt\Env;
use Strukt\Ref;
use Strukt\Raise;
use Strukt\Fs;
use Strukt\Type\Str; 
use Strukt\Type\Arr;
use Strukt\Type\Json;
// use Strukt\Ref;

class App{

	public static $app_type;
	public static $repo_pkgs;

	public static function create(string $app_type){

		if(!in_array($app_type, array("App:Cli", "App:Idx")))
			new Raise("Invalid application type must be either [App:Cli|App:Idx]!");

		static::$app_type = $app_type;
	}

	public static function getType():string{

		return static::$app_type;
	}

	public static function mayBeRepo(array $packages){

		static::$repo_pkgs = $packages;
	}

	public static function getRepo():array{

		return static::$repo_pkgs;
	}

	public static function getCls(string $class){

		$cls_name = Str::create($class);

		if($cls_name->contains("{{app}}"))
			$cls_name->replace("{{app}}", self::getName());

		return $cls_name->yield();
	}

	public static function newCls(string $class){

		$cls = self::getCls($class); 

		if(!class_exists($cls))
			new Raise(sprintf("%s does not exist!", $cls));

		return Ref::create($cls)->noMake()->getInstance();
	}

	public static function getName(){

		$filepath = Str::create(Env::get("root_dir"))
						->concat("/")
						->concat(Env::get("rel_app_ini"))
						->yield();

		if(!Fs::isFile($filepath))
			new Raise(sprintf("%s does not exist!", $filepath));

		$app_cfg = parse_ini_file($filepath);

		if(!is_array($app_cfg))
			new Raise(sprintf("%s|Please generate an application!", Env::get("rel_app_ini")));

		return $app_cfg["app-name"];
	}

	public static function packages($type){

		$pkgs = [];

		$type = Str::create($type);

		Arr::create(static::getRepo())->each(function($name, $cls) use(&$pkgs, $type){

			if($type->equals("installed")){

				if(class_exists($cls))
					$pkgs[] = $name;
			}

			if($type->equals("published")){

				if(class_exists($cls))
					if(Ref::create($cls)->make()->getInstance()->isPublished())
						$pkgs[] = $name;
			}
		});

		return $pkgs;
	}
}