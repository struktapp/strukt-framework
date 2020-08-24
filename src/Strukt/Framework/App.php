<?php

namespace Strukt\Framework;

use Strukt\Env;
use Strukt\Ref;
use Strukt\Raise;
use Strukt\Fs;
use Strukt\Type\Str; 
use Strukt\Type\Arr;
use Strukt\Type\Json;

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

		$type = Str::create(trim($type));

		if($type->equals("published")){

			Arr::create(array(

				"pkg_do"=>\App\Provider\EntityManagerAdapter::class,
				"pkg_audit"=>\Schema\Migration\VersionAudit::class,
				"pkg_books"=>\Schema\Migration\VersionAccounts::class,
				"pkg_roles"=>\Schema\Migration\VersionRoles::class
			))
			->each(function($name, $cls) use(&$pkgs){				

				if(class_exists($cls))
					$pkgs[] = $name;
			});
		}

		if($type->equals("installed")){

			$path = Str::create(Env::get("root_dir"))
							->concat("/")
							->concat("composer.json")
							->yield();

			$composer = Json::decode(Fs::cat($path));		

			Arr::create($composer["require"])->each(function($key, $val) use(&$pkgs){

				if(preg_match("/strukt\/pkg\-*/", $key)){

					$pkgs[] = Str::create($key)
						->replace("strukt/", "")
						->replace("-","_")
						->yield();
				}
			});
		}

		return $pkgs;
	}
}