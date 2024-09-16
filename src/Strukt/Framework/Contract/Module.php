<?php

namespace Strukt\Framework\Contract;

/**
* Strukt Module Reflector
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Module{

	/**
	* Module alias
	*
	* @var string
	*/
	protected $alias;

	/**
	* Module Reflection Object
	*
	* @var \ReflectionObject
	*/
	protected $reflect;

	/**
	* Constructor
	*
	* Initialize Reflector
	*/
	public function __construct(){

		$this->reflect = new \ReflectionObject($this);
	}

	/**
	* Getter for module directory
	*
	* @return string
	*/
	public function getBaseDir(){

		return dirname($this->reflect->getFileName());
	}

	/**
	* Module get name
	*
	* @return string
	*/
	public function getNamespace(){

		return $this->reflect->getName();
	}

	/**
	* Getter for module alias
	*
	* @return string
	*/
	public function getAlias(){

		return $this->alias;
	}
}