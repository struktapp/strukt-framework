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

		$settings = array(

			"App:Idx"=>array(

				"providers"=>array(

					\Strukt\Framework\Provider\Validator::class,
					\App\Provider\Logger::class,
					\App\Provider\Facet::class
				),
				"middlewares"=>array(

					// \App\Middleware\Cors::class,
					\Strukt\Router\Middleware\Session::class,
					\Strukt\Router\Middleware\Authorization::class,
					\Strukt\Router\Middleware\Authentication::class,
					\Strukt\Framework\Middleware\Validator::class,
				)
			),
			"App:Cli"=>array(

				"providers"=>array(

					\Strukt\Framework\Provider\Validator::class,
					\App\Provider\Logger::class,
					\App\Provider\Facet::class
				),
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
					\Strukt\Framework\Console\Command\PackageExport::class
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