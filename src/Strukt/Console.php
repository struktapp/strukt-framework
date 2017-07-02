<?php

namespace Strukt;

/**
* Console Loader
*
* @author Moderator <pitsolu@gmail.com>
*/
class Console extends \Strukt\Console\Application{

	/**
	* Root directory path
	*
	* @var string
	*/
	private static $rootDir;

	/**
	* Application directory path
	*
	* @var string
	*/
	private static $appDir;

	/**
	* Static directory path - Optional
	*
	* @var string
	*/
	private static $staticDir;

	/**
	* Constructor loads Strukt Framework in-build applications
	*
	* Commands loaded:
	*
	* 	\Strukt\Console\Command\ApplicationGenerator
	* 	\Strukt\Console\Command\RouterGenerator
	* 	\Strukt\Console\Command\ModuleGenerator
	* 	\Strukt\Console\Command\ApplicationLoaderGenerator
	*/
	public function __construct(Array $config = null){

		if(is_null($config)){

			$config = array(

				"appName"=>"Strukt Console",
				"loadNativeCmds"=>true,
				"labelStruktSect"=>false
			);
		}
		else{

			$configKeys = array_keys($config);

			if(!in_array("appName", $configKeys))
				$config["appName"] = "Strukt Console";

			if(!in_array("loadNativeCmds", $configKeys))
				$config["loadNativeCmds"] = true;

			if(!in_array("labelStruktSect", $configKeys))
				$config["labelStruktSect"] = false;
		}

		parent::__construct($config["appName"]);

		if($config["labelStruktSect"])
			$this->addCmdSect("Strukt");

		if($config["loadNativeCmds"]){

			$this->add(new \Strukt\Console\Command\ApplicationGenerator);
			$this->add(new \Strukt\Console\Command\RouterGenerator);
			$this->add(new \Strukt\Console\Command\ModuleGenerator);
			$this->add(new \Strukt\Console\Command\ApplicationLoaderGenerator);
		}
	}

	/**
	* Declare application directory
	*
	* @param $appDir
	*/
	public static function useAppDir($appDir){

		self::$appDir = $appDir;
	}

	/**
	* Getter for application directory
	*
	* @return string
	*/
	public static function getAppDir(){

		return self::$appDir;
	}

	/**
	* Declare root directory
	*
	* @param $appDir
	*/
	public static function useRootDir($rootDir){

		self::$rootDir = $rootDir;
	}

	/**
	* Getter for root directory
	*
	* @return string
	*/
	public static function getRootDir(){

		return self::$rootDir;
	}

	/**
	* Declare static directory
	*
	* @param $appDir
	*/
	public static function useStaticDir($staticDir){

		self::$staticDir = $staticDir;
	}

	/**
	* Getter for static directory
	*
	* @return string
	*/
	public static function getStaticDir(){

		return self::$staticDir;
	}
}