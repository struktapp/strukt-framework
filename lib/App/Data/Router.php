<?php

namespace App\Data;

/**
* Abstract Router class to be extended by Router
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Router extends \App\Base\Registry{

	/**
	* Getter for request params, uses \Symfony\Component\HttpFoundation\Request
	*
	* @return mixed
	*/
	public function param($key){

		if(!self::getInstance()->exists("request"))
			throw new \Exception("Request object (key:[request]) is not in in Strukt\Core\Registy!");

		$req = self::get('request');

		$contents = $req->getContent();

		if($req->getMethod() === 'GET'){

			$reqJson = json_decode($contents, 1);

			return $reqJson[$key];
		}

		if($req->query->has($key))
			return $req->query->get($key);

		if($req->request->has($key))
			return $req->request->get($key);
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
		// $res->setStatusCode(200);
		$res->headers->set('Location', $url);

		$res->send();
	}

	/**
	* HTML File Response
	*
	* @param string $pathtofile relative to static folder
	*
	* @return \Symfony\Component\HttpFoundation\Response
	*/
	protected function htmlfile($pathtofile, $code = 200){

		if(\Strukt\Fs::isFile($pathtofile)){

			$res = self::get("Response.Ok")->exec();
			$res->headers->set("Content-Type", "text/html");
			$res->setStatusCode($code);
			$res->setContent(\Strukt\Fs::cat($pathtofile));			
		}
		else
			$res = self::get("Response.NotFound")->exec();

		return $res;
	}

	/**
	* JSON Serialiser Response
	*
	* @param array $body
	* @param int $code
	*
	* @return \Symfony\Component\HttpFoundation\Response
	*/
	protected function json(array $body, $code = 200){

		$res = self::get("Response.Ok")->exec();
		$res->headers->set("Content-Type", "application/json");
		$res->setStatusCode($code);
		$res->setContent(json_encode($body));	

		return $res;
	}

	/**
	* HTML Response
	*
	* @param string $body
	* @param int $code
	*
	* @return \Symfony\Component\HttpFoundation\Response
	*/
	protected function html($body, $code = 200){

		$res = self::get("Response.Ok")->exec();
		$res->headers->set("Content-Type", "text/html");
		$res->setStatusCode($code);
		$res->setContent($body);	

		return $res;
	}
}
