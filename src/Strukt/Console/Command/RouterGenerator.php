<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* generate:router     Generate Module Router - ACCEPTS NO ARGS
*
* Usage:
*
*	Namepace:	<module>
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
class RouterGenerator extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$registry = \Strukt\Core\Registry::getInstance();

		if(!$registry->exists("dir.root"))
			throw new \Exception("Strukt root dir not defined!");

		if(!$registry->exists("dir.app"))
			throw new \Exception("Strukt app dir not defined!");

		$rootDir = $registry->get("dir.root");
		$appDir = $registry->get("dir.app");
		$moduleList = unserialize($registry->get("module-list"));

		/**
		* Module Name
		*/
		$prompt["module"] = "Module Name: ";

		while(empty($router["id"]["namespace"])){

			$module = trim($in->getInput($prompt["module"]));

			if(in_array($module, array_keys($moduleList))){

				$namespace = $moduleList[$module]["base-ns"];

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
			
			$nsPath = str_replace("\\", "/", $namespace);
			$srcDir = sprintf("%s/%s/src", $rootDir, $appDir);
			$path = sprintf("%s/%s", $srcDir, $nsPath);
		}

		/**
		* Router Name
		*/
		$prompt["router_name"] = "Router Name: ";

		while(empty($router["id"]["name"])){

			$router["id"]["name"] = trim($in->getInput($prompt["router_name"]));

			$pattern = sprintf("/^%s$/i", $router["id"]["name"]);

			if(!empty(preg_grep($pattern, $moduleList[$module]["Router"]))){

				echo "\n  Router already exists!\n\n";
			}

			if(empty($router["id"]["name"])){

				$prompt["router_name"] = "Router Name [REQUIRED!]: ";

				continue;
			}
			
			$router["id"]["name"] = str_replace(array("router", "Router"), "", $router["id"]["name"]);
		}
		
		$router["id"]["extends"] = "\App\Data\Router";

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
						else
							$method["params"][] = $param;
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

			"Psr\Http\Message\RequestInterface",
			"Psr\Http\Message\ResponseInterface"
		);

		$builderInstance = new \Strukt\Generator\ClassBuilder($router["id"]);

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

			if(in_array("params", array_keys($method)))
				$func["params"] = $method["params"];
				
			$builderInstance->addMethod($func, new \Strukt\Generator\Annotation\Basic($annotations));
		}

		\Strukt\Fs::touchWrite(sprintf("%s/Router/%s.php", $path, $router["id"]["name"]), sprintf("<?php\n%s", $builderInstance));

		$out->add("Router genarated successfully.\n");
	}
}