<?php

namespace App\Data;

/**
* Abstract Router class to be extended by Router
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Router extends \App\Base\Registry{
	/**
	* Getter for request params, uses \Strukt\Rest\Request
	*
	* @return mixed
	*/
	public function param($key){

		if(!self::getInstance()->exists("servReq"))
			throw new \Exception("Server Request object (key:[servReq]) is not in in Strukt\Core\Registy!");

		$serverRequest = self::get("servReq");

		$body = $serverRequest->getParsedBody();

		if($body instanceof \Psr\Http\Message\StreamInterface)
			$body = json_decode((string)$body);

		return $body[$key];
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

		$res = self::get("Response.Redirected")->exec();
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

		if(\Strukt\Fs::isFile($pathtofile)){

			$res = self::get("Response.Ok")->exec();
			$res = $res->withHeader("content-type", "text/html");
			$res->getBody()->write(\Strukt\Fs::cat($pathtofile));			
		}
		else
			$res = self::get("Response.NotFound");

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

		$res = self::get("Response.Ok")->exec();
		$res = $res->withHeader("content-type", "application/json");
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

		$res = self::get("Response.Ok")->exec();
		$res = $res->withHeader("content-type", "text/html");
		$res->getBody()->write($body);	

		return $res;
	}
}
