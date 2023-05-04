<?php

namespace Strukt\Framework\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Contract\Middleware\AbstractMiddleware;
// use Strukt\Http\Response\Json as JsonResponse;
// use Strukt\Http\Exception\NotFound as NotFoundException;
// use Strukt\Http\Response\Plain as Response;

use Strukt\Http\Error\BadRequest;
use Strukt\Contract\Http\Error\HttpErrorInterface;
use Strukt\Http\Error\Any as HttpError;
use Strukt\Http\Exec as HttpExec;

use Strukt\Framework\App as FrameworkApp;
use Strukt\Type\Json;

/**
* @Name(valid)
* @Requires(strukt.router)
*/
class Validator extends AbstractMiddleware implements MiddlewareInterface{

	public function __construct(){

		//
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$method = $request->getMethod(); 
		$uri = $request->getRequestUri();

		$headers = [];
		if(\Strukt\Reg::exists("strukt.useJsonError"))
			if(\Strukt\Reg::get("strukt.useJsonError"))
				$headers = ["Content-Type"=>"application/json"];

		try{

			$routeLs = $this->core()->get("strukt.router");
			$route = $routeLs->matchToken("@forms")->getRoute($method, $uri);

			if(!is_null($route)){

				$tokens = $route->getTokens();

				if(!empty($tokens)){

					foreach($tokens as $token)
						if(str_starts_with($token, "@form"))
							break;

					list($token, $method, $cls) = preg_split("/(:|\|)/", $token);

					$app_type = FrameworkApp::getType();
					if($method == "OPTIONS" &&  $app_type == "App:Idx"){

						$body = Json::decode($request->getContent());
						foreach($body as $name=>$val)
							$request->request->set($name, $val);
					}

					$ref = \Strukt\Ref::create($cls);
					$messages = $ref->makeArgs([$request])->method("validate")->invoke();

					if(!$messages["success"])
						$response = new BadRequest(Json::encode($messages), $headers);
				}
			}
		}
		catch(\Exception $e){

			$code = 500;
	 		if(HttpError::isCode($e->getCode()))
	 			$code = $e->getCode();

	 		$response = new HttpError($e->getMessage(), $code, $headers);
		}

		if($response instanceof HttpErrorInterface)
	 		HttpExec::make($response)->withHeaders()->run();

		return $next($request, $response);
	}
}