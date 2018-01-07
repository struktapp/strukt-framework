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
	// public function getRequest(){

	// 	return new \Strukt\Rest\Request();
	// }

	/**
	* Getter for request params, uses \Strukt\Rest\Request
	*
	* @return mixed
	*/
	public function param($key){

		// return \Strukt\Rest\Request::getParam($key);

		return $this->get("servReq")->getAttribute($key);
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

		// \Strukt\Rest\Response::redirect($url);

		$res = $registry->get("Response.Redirected")->exec();

		$res = $res->withStatus(200)->withHeader('Location', $url);

		\Strukt\Router\Router::emit($res);
	}

	/**
	* HTML File Response
	*
	* @param string $pathtofile relative to static folder
	*
	* @return \Strukt\Rest\ResponseType\HtmlFileResponse
	*/
	protected function htmlfile($pathtofile, $code = 200){

		// return new \Strukt\Rest\ResponseType\HtmlFileResponse($pathtofile);

		if(\Strukt\Fs::isFile($pathtofile)){

			$res = new \Kambo\Http\Message\Response($code, array("content-type"=>"text/html"));
			$res->getBody()->write(\Strukt\Fs::cat($pathtofile));
		}
		else
			$res = $this->get("Response.NotFound");

		return $res;
	}

	/**
	* JSON Serialiser Response
	*
	* @param array $body
	* @param int $code
	*
	* @return \Strukt\Rest\ResponseType\JsonResponse
	*/
	protected function json(array $body, $code = 200){

		// return new \Strukt\Rest\ResponseType\JsonResponse($body, $code);

		$res = new \Kambo\Http\Message\Response($code, array("content-type"=>"application/json"));
		$res->getBody()->write(json_encode($body));

		return $res;
	}

	/**
	* HTML Response
	*
	* @param string $body
	* @param int $code
	*
	* @return \Strukt\Rest\ResponseType\HtmlResponse
	*/
	protected function html($body, $code = 200){

		// return new \Strukt\Rest\ResponseType\HtmlResponse($body, $code);

		$res = new \Kambo\Http\Message\Response($code, array("content-type"=>"text/html"));
		$res->getBody()->write($body);

		return $res;
	}
}
