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

use Strukt\Package\PkgDo;
use Strukt\Package\PkgRoles;
use Strukt\Package\PkgAudit;
use Strukt\Package\PkgBooks;
use Strukt\Package\PkgTests;
use Strukt\Package\PkgAsset;

class App{

	private $cls;

	public function __construct(){

		//
	}

	public static function getCls(string $class){

		return Str::create($class)
						->replace("{{app}}", self::getName())
						->yield();
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

		Arr::create(array(

			"pkg_do"=>PkgDo::class,
			"pkg_audit"=>PkgAudit::class,
			"pkg_books"=>PkgBooks::class,
			"pkg_roles"=>PkgRoles::class,
			"pkg_tests"=>PkgTests::class,
			"pkg_asset"=>PkgAsset::class
		))
		->each(function($name, $cls) use(&$pkgs, $type){				

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