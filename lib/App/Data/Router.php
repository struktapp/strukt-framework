<?php

namespace App\Data;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
* Abstract Router class to be extended by Router
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Router extends \App\Base\Registry{

	/**
	* Request redirect
	*
	* @return void
	*/
	protected function redirect($url, $code = 302, $headers = []){

		return new RedirectResponse($url, $code, $headers);
	}

	/**
	* HTML File Response
	*
	* @param string $pathtofile relative to static folder
	*
	* @return \Symfony\Component\HttpFoundation\Response
	*/
	protected function htmlfile($filepath, $code = 200){

		if(\Strukt\Fs::isFile($filepath)){	

			$content = \Strukt\Fs::cat($filepath);

			return $this->html($content, $code);	
		}

		throw new \Strukt\Router\Exception\NotFoundException();
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

		return new JsonResponse($body, $code);
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

		return new Response($body, $code, array("Content-Type"=>"text/html"));	
	}
}
