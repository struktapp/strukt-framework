<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* route:make     Generate Module Router - ACCEPTS NO ARGS
*
* Usage:
*
*	Module:	<module>
*	 example:
*				PayrollAuthModule
*
*	Router: <name[Router]>
*	   example:
*				User
*				UserRouter
*
*	Methods:
*		Method Route: /<route>
*			example:
*				/user/all
*				user/all
*
*		Method Permission: [<perm>]
*			example:
*				user-all
*				user_all
*
*		Method Parameters: [<param1>,<param2>, ...]
*			example:
*				id
*				id, role
*
*		Method Action: [<action1>, <action2>, ...]
*			default: GET
*			options: GET, POST, DELETE
*/
class RouteMake extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$root_dir = env("root_dir");
		$app_dir = env("rel_appsrc");

		$routeList = [];
		$moduleList = [];

		$modules = map(reg("nr"))->detach("modules");
		arr($modules)->each(function($alias, $ns) use(&$moduleList, &$routeList){

			list($app, $module, $app_module) = str($ns)->split("\\");
			$moduleList[$app_module] = array(

				"app"=>$app,
				"module"=>$module,
				"app_module"=>$app_module,
				"ns"=>sprintf("%s\\%s", $app, $module),
				"alias"=>$alias
			);

			$routeList[$app_module] = reg(sprintf("nr.routes.%s", $alias));
		});

		$module_names = arr($moduleList)->column("app_module");
		$modules_ls = arr($module_names)->each(function($k, $v){

			return str(++$k)->concat(".")->concat($v)->yield();
		});

		echo(sprintf("%s\n", implode("\n", $modules_ls->yield())));

		/**
		* Module Name
		*/
		$prompt["module"] = "Select Module: ";
		while(empty($router["id"]["namespace"])){

			$selected = trim($in->getInput($prompt["module"]));
			$selected = (int)$selected;
			--$selected;

			$module = $module_names[$selected];
			if($module){

				$namespace = $moduleList[$module_names[$selected]]["ns"];
				$router["id"]["namespace"] = sprintf("%s\Router", $namespace);
			}
			else{

				echo "\n  Invalid Module Name!\n\n";
				continue;
			}

			if(empty($module)){

				$prompt["module"] = "Module Name [REQUIRED!]: ";
				continue;
			}
			
			$ns_path = str_replace("\\", "/", $namespace);
			$path = str(ds($root_dir))
						->concat(ds($app_dir))
						->concat(ds($ns_path))
						->yield();
		}

		/**
		* Router Name
		*/
		$prompt["router_name"] = "Router Name: ";
		while(empty($router["id"]["name"])){

			$router["id"]["name"] = trim($in->getInput($prompt["router_name"]));
			$pattern = sprintf("/^%s$/i", $router["id"]["name"]);
			if(!empty(preg_grep($pattern, $routeList[$module])))
				echo "\n  Router already exists!\n\n";

			if(empty($router["id"]["name"])){

				$prompt["router_name"] = "Router Name [REQUIRED!]: ";
				continue;
			}
			
			$router["id"]["name"] = str_replace(array("router", "Router"), "", $router["id"]["name"]);
		}
		
		$router["id"]["extends"] = sprintf("\%s", \Strukt\Framework\Contract\Router::class);

		echo "\n";
		$continue = true;
		while($continue){

			$prompt = null;
			$method = null;
			$invalid = null;

			/**
			* Route
			*/
			$prompt["route"] = " Route: ";
			while(empty($method["route"])){

				$method["route"] = trim($in->getInput($prompt["route"]));
				if(empty($method["route"])){

					$prompt["route"] = " Route [REQUIRED!]: ";
					continue;
				}

				$method["route"] = sprintf("/%s", trim($method["route"],"/"));
			}

			/**
			* Permission
			*/
			$prompt["perm"] = " Permission (Optional): ";
			$method["perm"] = trim($in->getInput($prompt["perm"]));

			/**
			* Function
			*/
			$prompt["method_name"] = " Function: ";
			while(empty($method["name"])){

				$method["name"] = trim($in->getInput($prompt["method_name"]));
				if(empty($method["name"]))	
					$prompt["method_name"] = " Function [REQUIRED!]: ";
			}
			
			/**
			* Function Parameters
			*/
			$invalid["params"] = true;
			$prompt["params"] = " Function Parameter(s) separater[,]: ";
			while($invalid["params"]){

				$params = trim($in->getInput($prompt["params"]));
				if(!empty($params)){

					if(!preg_match("/^[\w\s,]+$/", $params)){

						echo "\n  Invalid Input!\n\n";
						$prompt["params"] = " Function Parameter(s) separater[,]: ";

						continue;
					}

					foreach(explode(",", trim($params, ",")) as $param){

						$param = trim($param);
						if(preg_match("/^\w+\s+\w+$/", $param)){
							
							$arrParam = preg_split("/[\s,]+/", $param);
							$method["params"][next($arrParam)] = reset($arrParam); 
						}
						else $method["params"][] = $param;
					}
				}

				$invalid["params"] = false;
			}

			/**
			* Http Methods Allowed
			*/
			$invalid["actions"] = true;
			$prompt["actions"] = " Http Method(s) separater[,] (GET): ";
			while($invalid["actions"]){

				$actions = trim($in->getInput($prompt["actions"]));
				$method["actions"] = "GET";
				if(!empty($actions)){

					if(!preg_match("/^(GET|POST|DELETE|,|\s)+$/", $actions)){

						echo "\n  Invalid Input!\n\n";
						$prompt["actions"] = " Http Method(s) separate[,] (GET): ";

						continue;
					}

					$method["actions"] = array_map(function($action){

						return trim($action);

					}, explode(",", trim($actions, ",")));

					$invalid["actions"] = false;
				}

				$invalid["actions"] = false;
			}

			$router["methods"][] = $method;

			/**
			* Continue ..
			*/
			$continue = trim($in->getInput("Add Method (y): "));
			if(empty($continue))
				$continue = "y";
			
			if($continue == "y")
				continue;
			
			$continue = false;
		}

		$router["id"]["use"] = array(

			\Strukt\Http\Request::class,
			\Strukt\Http\Reponse\Plain::class,
		);

		$generator = generator(["declaration"=>$router["id"]], ["method_notes"=>true]);
		foreach($router["methods"] as $method){

			$annotations = array(

				"Route"=>$method["route"], 
				"Method"=>$method["actions"]
			);

			if(!empty($method["perm"]))
				$annotations["Permission"] = $method["perm"];
				
			$func = array(

				"name"=>$method["name"],
				"body"=>"//"
			);

			$method["params"]["request"] = "Request";
			if(in_array("params", array_keys($method)))
				$func["params"] = $method["params"];

			$func["annotations"] = $annotations;
			$generator->method($func);
		}

		$file_path = str($path)
			->prepend("/")
			->concat(ds("Router"))
			->concat(str($router["id"]["name"])->concat(".php"))
			->yield();

		\Strukt\Fs::touchWrite($file_path, sprintf("<?php\n%s", $generator));
		$out->add("Router genarated successfully.\n");
	}
}