<?php

namespace Strukt\Framework;

use Strukt\Core\Collection;
use Strukt\Core\Map;

/**
* Strukt Application Core Registry
*
* @author Moderator <pitsolu@gmail.com>
*/
class Registry{

	/**
	* Singleton Registry Instance
	*
	* @var Strukt\Core\Map $map
	*/
	private static $registry = null;

	/**
	* Raw registry
	*
	* @var Strukt\Core\Map $map
	*/
	private $register = null;

	/**
	* Constructor initialize Strukt global register
	*/
	private function __construct(){

		$this->register = new Map(new Collection("Strukt Registry"));
	}

	/**
	* Getter for Singleton registry instance
	*
	* @return \Strukt\Framework\Registry
	*/
	public static function getInstance(){

		if(is_null(static::$registry))
			static::$registry = new self;

		return static::$registry;
	}

	/**
	* Getter registry value
	*
	* @param string $key
	*
	* @return mixed
	*/
	public function get($key){

		return $this->register->get($key);
	}

	/**
	* Setter for registry value
	*
	* @param string $key
	* @param string $val
	*
	* @return void
	*/
	public function set($key, $val){

		$this->register->set($key, $val);
	}

	/**
	* Remove registry value
	*
	* @param string $key
	*
	* @return void
	*/
	public function remove($key){

		$this->register->remove($key);
	}

	/**
	* Check existence of registry value
	*
	* @param string $key
	*
	* @return boolean
	*/
	public function exists($key){

		return $this->register->exists($key);
	}
}