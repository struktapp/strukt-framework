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

/**
* @Name(valid)
* @Requires(strukt.router)
*/
class Validator extends AbstractMiddleware implements MiddlewareInterface{

	public function __construct(){

		//
	}

	public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next){

		$method = $request->getMethod(); 
		$uri = $request->getRequestUri();

		$routeLs = $this->core()->get("strukt.router");
		$route = $routeLs->matchToken("@forms")->getRoute($method, $uri);

		if(!is_null($route)){

			$tokens = $route->getTokens();

			if(!empty($tokens)){

				foreach($tokens as $token)
					if(str_starts_with($token, "@form"))
						continue;

				list($token, $method, $cls) = preg_split("/(:|\|)/", $token);

				$app_type = \Strukt\Framework\App::getType();
				if($method == "OPTIONS" &&  $app_type == "App:Idx"){

					$body = \Strukt\Type\Json::decode($request->getContent());
					foreach($body as $name=>$val)
						$request->request->set($name, $val);
				}

				try{

					$ref = \Strukt\Ref::create($cls);
					$messages = $ref->makeArgs([$request])->method("validate")->invoke();

					if(!$messages["success"])
						return new JsonResponse($messages, 400); //Bad Request Error 400		
				}
				catch(\Exception $e){

			 		$code = 500;
			 		if($e instanceof HttpExceptionInterface)
			 			$code = $e->getCode();

			 		$response = new Response($e->getMessage(), $code);
			 	}
			}
		}

		return $next($request, $response);
	}
}