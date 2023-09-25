<?php

namespace Strukt\Framework\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Error\BadRequest;
use Strukt\Cmd;

/**
* @Name(valid)
*/
class Validator implements MiddlewareInterface{

	public function __construct(){

		//
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$action = $request->getMethod(); 

		$headers = [];
		if(env("json.validation.err"))
			$headers = ["Content-Type"=>"application/json"];

		$configs = Cmd::ls("^type:route");
		$route = reg("route.current");
		$name = sprintf("type:route|path:%s|action:%s", $route, $request->getMethod());

		if(in_array($name, $configs)){
		
			$tokq = token($name);

			if($tokq->has("form")){

				$cls = $tokq->get("form");
				$method = $tokq->get("method");	

				if($action == "OPTIONS" &&  config("app.type") == "App:Idx"){

					$body = json($request->getContent())->decode();
					foreach($body as $name=>$val)
						$request->request->set($name, $val);
				}

				$messages = \Strukt\Ref::create($cls)->makeArgs([$request])->method("validate")->invoke();
				if(!$messages["success"])
					$response = new BadRequest(json($messages)->encode(), $headers);
			}
		}
	
		return $next($request, $response);
	}
}