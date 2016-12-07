<?php

namespace App\Data;

/**
* Abstract Router class to be extended by Router
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Router extends \App\Base\Registry{

	/**
	* Get HTTP Request
	* 
	* @return \Strukt\Rest\Request
	*/
	public function getRequest(){

		return new \Strukt\Rest\Request();
	}

	/**
	* Getter for request params, uses \Strukt\Rest\Request
	*
	* @return mixed
	*/
	public function param($key){

		return \Strukt\Rest\Request::getParam($key);
	}

	/**
	* Session object getter
	*
	* @return \App\Session\Native
	*/
	public function session(){

		return new \App\Session\Native();
	}

	/**
	* Request redirect
	*
	* @return void
	*/
	protected function redirect($url){

		\Strukt\Rest\Response::redirect($url);
	}

	/**
	* HTML File Response
	*
	* @param string $pathtofile relative to static folder
	*
	* @return \Strukt\Rest\ResponseType\HtmlFileResponse
	*/
	protected function htmlfile($pathtofile){

		return new \Strukt\Rest\ResponseType\HtmlFileResponse($pathtofile);
	}

	/**
	* JSON Serialiser Response
	*
	* @param array $body
	* @param int $code
	*
	* @return \Strukt\Rest\ResponseType\JsonResponse
	*/
	protected function json(array $body, $code = null){

		return new \Strukt\Rest\ResponseType\JsonResponse($body, $code);
	}

	/**
	* HTML Response
	*
	* @param string $body
	* @param int $code
	*
	* @return \Strukt\Rest\ResponseType\HtmlResponse
	*/
	protected function html($body, $code = null){

		return new \Strukt\Rest\ResponseType\HtmlResponse($body, $code);
	}
}
