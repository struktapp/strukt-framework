<?php

namespace Strukt\Contract;

use Strukt\Http\Response;
use Strukt\Http\RedirectResponse;
use Strukt\Http\JsonResponse;
use Strukt\Http\Exception\NotFoundException;
use Strukt\Fs;

/**
* Abstract Router class to be extended by Router
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Router extends AbstractService{

	/**
	* Internal request redirect
	*
	* @param string $uri
	* @param string $method
	* @param array $params
	*
	* @return Strukt\Contract\ResponseInterface
	*/
	protected function redirect(string $uri, string $method="POST", array $params=[]){

		$router = $this->core()->get("app.router");

		$router = $router->getRoute($method, $uri);

		foreach($params as $param)
			$router->addParam($param);

		return $router->exec();
	}

	/**
	* External request redirect
	* 
	* @param string $url
	* @param string $code
	* @param array $headers
	* 
	* @return void
	*/
	protected function externalRedirect($url, $code = 302, $headers = []){

		return new RedirectResponse($url, $code, $headers);
	}

	/**
	* HTML File Response
	*
	* @param string $pathtofile relative to static folder
	*
	* @return \Strukt\Http\Response
	*/
	protected function htmlfile($filepath, $code = 200, $headers = []){

		if(Fs::isFile($filepath)){	

			$content = Fs::cat($filepath);

			return $this->html($content, $code, $headers);	
		}

		throw new NotFoundException();
	}

	/**
	* JSON Serialiser Response
	*
	* @param array $body
	* @param int $code
	*
	* @return \Strukt\Http\Response
	*/
	protected function json(array $body, $code = 200, $headers = []){

		return new JsonResponse($body, $code, $headers);
	}

	/**
	* HTML Response
	*
	* @param string $body
	* @param int $code
	*
	* @return \Strukt\Http\Response
	*/
	protected function html($body, $code = 200, $headers = []){

		$headers = array_merge($headers, array("Content-Type"=>"text/html"));

		return new Response($body, $code, $headers);	
	}
}
