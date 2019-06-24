<?php

namespace App\Data;

use Strukt\Http\Response;
use Strukt\Http\RedirectResponse;
use Strukt\Http\JsonResponse;
use Strukt\Router\Exception\NotFoundException;
use Strukt\Fs;

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
	* @return \Strukt\Http\Response
	*/
	protected function htmlfile($filepath, $code = 200){

		if(Fs::isFile($filepath)){	

			$content = Fs::cat($filepath);

			return $this->html($content, $code);	
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
	protected function json(array $body, $code = 200){

		return new JsonResponse($body, $code);
	}

	/**
	* HTML Response
	*
	* @param string $body
	* @param int $code
	*
	* @return \Strukt\Http\Response
	*/
	protected function html($body, $code = 200){

		return new Response($body, $code, array("Content-Type"=>"text/html"));	
	}
}
