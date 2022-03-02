<?php

namespace Strukt\Contract;

use Strukt\Http\Response;
use Strukt\Http\Request;
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
	* @param \Strukt\Http\Request $request (optional)
	*
	* @return Strukt\Contract\ResponseInterface
	*/
	protected function redirect(string $uri, Request $request = null){

		$method = "POST";
		if(preg_match("/\:/", $uri))
			list($method, $uri) = explode(":", $uri);

		$router = $this->core()->get("strukt.router");

		$route = $router->getRoute($method, $uri);

		$params = $route->getEvent()->getParams();

		if(!is_null($request))
			foreach($params as $name=>$param)
				if($param == Request::class)
					$route->setParam($name, $request);

		return $route->exec();
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
