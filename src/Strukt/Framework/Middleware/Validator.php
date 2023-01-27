<?php

namespace Strukt\Framework\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Contract\Middleware\AbstractMiddleware;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Http\Exception\NotFound as NotFoundException;
use Strukt\Http\Response\Plain as Response;
use Strukt\Contract\Http\Exception\HttpExceptionInterface;

class Validator extends AbstractMiddleware implements MiddlewareInterface{

	public function __construct(){

		//
	}

	public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next){

		$forms = $this->core()->get("strukt.forms");

		$urlParser = new \Strukt\Router\UrlParser(array_keys($forms));
		$pattern = $urlParser->whichPattern($request->getRequestUri());

		try{

			if(!empty($forms[$pattern])){

				list($method, $cls) = explode(":", $forms[$pattern]);

				if($method != $request->getMethod())
					throw new NotFoundException();

				$ref = \Strukt\Ref::create($cls);
				$messages = $ref->makeArgs([$request])->method("validate")->invoke();

				if(!$messages["success"])
					return new JsonResponse($messages, 400); //Bad Request Error 400
			}
		}
		catch(\Exception $e){

	 		$code = 500;
	 		if($e instanceof HttpExceptionInterface)
	 			$code = $e->getCode();

	 		$response = new Response($e->getMessage(), $code);
	 	}

		return $next($request, $response);
	}
}