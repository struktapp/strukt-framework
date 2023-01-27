<?php

namespace Strukt\Framework\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Contract\Middleware\AbstractMiddleware;
use Strukt\Http\Response\Json as JsonResponse;

class Validator extends AbstractMiddleware implements MiddlewareInterface{

	public function __construct(){

		//
	}

	public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next){

		$forms = $this->core()->get("strukt.forms");

		$urlParser = new \Strukt\Router\UrlParser(array_keys($forms));
		$pattern = $urlParser->whichPattern($request->getRequestUri());

		if(!empty($forms[$pattern])){

			$ref = \Strukt\Ref::create($forms[$pattern]);
			$messages = $ref->makeArgs([$request])->method("validate")->invoke();

			if(!$messages["success"])
				return new JsonResponse($messages, 400); //Bad Request Error 400
		}

		return $next($request, $response);
	}
}