<?php

namespace Strukt\Package;

use Strukt\Framework\Contract\Package as PackageInterface;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class Core implements PackageInterface{

	private $manifest;

	public function __construct(){

		$this->manifest = array(

			"cmd_name"=>"Strukt",
			"package"=>"core",
			"files"=>null
		);
	}

	/**
	 * @return void
	 */
	public function preInstall():void{

		//
	}

	/**
	 * @param string $type
	 * 
	 * @return array
	 */
	public function getSettings(string $type):array{

		$middlewares = arr([

			"jwt"=>\App\Middleware\Jwt::class,
			"cors"=>\App\Middleware\Cors::class,
			"sess"=>\Strukt\Router\Middleware\Session::class,
			"authz"=>\Strukt\Router\Middleware\Authorization::class,
			"auth"=>\Strukt\Router\Middleware\Authentication::class,
			"valid"=>\Strukt\Framework\Middleware\Validator::class,

		])->each(function($alias, $class){

			if(negate(class_exists($class)))
				return null;

			$alias = str($alias);
			if($alias->equals("jwt"))
				if(class_exists(\Strukt\Jwt::class))
					return $class;

			if($alias->notEquals("jwt"))
				return $class;
		});

		$providers = arr([

			"valid"=>\Strukt\Framework\Provider\Validator::class,
			"logger"=>\App\Provider\Logger::class,
			"faker"=>\App\Provider\Faker::class,
			"facet"=>\App\Provider\Facet::class

		])->each(function($alias, $class){

			if(negate(class_exists($class)))
				return null;

			$alias = str($alias);
			if($alias->equals("logger"))
				if(class_exists(\Monolog\Logger::class))
					return $class;

			if($alias->equals("faker"))
				if(class_exists(\Faker\Generator::class))
					return $class;

			if($alias->notEquals("logger") && $alias->notEquals("faker"))
				return $class;
		});

		$providers = $providers->filter()->yield();
		$middlewares = $middlewares->filter()->yield();

		$settings = array(

			"App:Idx"=>array(

				"providers"=>$providers,
				"middlewares"=>$middlewares
			),
			"App:Cli"=>array(

				"providers"=>$providers,
				"middlewares"=>array(

					\App\Middleware\XSession::class,
					\Strukt\Router\Middleware\Authentication::class,
					\Strukt\Framework\Middleware\Validator::class,
				),
				"commands"=>array(

					\Strukt\Framework\Console\Command\PackagePublisher::class,
					\Strukt\Framework\Console\Command\PackageList::class,
					\Strukt\Framework\Console\Command\PackageInfo::class,
					\Strukt\Framework\Console\Command\PackageMake::class,
					\Strukt\Framework\Console\Command\PackageAdd::class,
					\Strukt\Framework\Console\Command\PackageCopy::class,
					\Strukt\Framework\Console\Command\PackageExport::class,
					\Strukt\Framework\Console\Command\PackageDiff::class
				)
			)
		);

		return $settings[$type];
	}

	/**
	 * @return string
	 */
	public function getName():string{

		return $this->manifest["package"];
	}

	/**
	 * @return string
	 */
	public function getCmdName():string{

		return $this->manifest["cmd_name"];
	}

	/**
	 * @return array
	 */
	public function getFiles():array|null{

		return $this->manifest["files"];
	}

	/**
	 * @return array|null
	 */
	public function getModules():array|null{

		return null;
	}

	/**
	 * @return bool
	 */
	public function isPublished():bool{

		return true;
	}

	/**
	 * @return array|null
	 */
	public function getRequirements():array|null{

		return null;
	}

	/**
	 * @return void
	 */
	public function postInstall():void{

		//
	}
}