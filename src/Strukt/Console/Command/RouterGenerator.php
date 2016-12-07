<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

/**
* generate:router     Generate Module Router - ACCEPTS NO ARGS
*
* Usage:
*
*	Namepace:	<application>/<module>[/Router]
*	 example:
*				PayrollSystem/AuthModule/Router
*				PayrollSystem/AuthModule
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

		$rootDir = \Strukt\Console::getRootDir();
		if(empty($rootDir))
			throw new \Exception("Strukt root dirnot defined! Use Strukt\Console::useRootDir(<root_dir>)");

		$appDir = \Strukt\Console::getAppDir();
		if(empty($appDir))
			throw new \Exception("Strukt app dir not defined! Use Strukt\Console::useAppDir(<app_dir>)");

		$prompt["namespace"] = " \nNamespace: ";

		while(empty($router["id"]["namespace"])){

			$namespace = trim($in->getInput($prompt["namespace"]));

			if(empty($namespace)){

				$prompt["namespace"] = "Namespace [REQUIRED!]: ";

				continue;
			}
			
			$nsPath = str_replace("\\", "/", $namespace);
			$srcDir = sprintf("%s/%s/src", $rootDir, $appDir);
			$path = sprintf("%s/%s", $srcDir, $nsPath);
			$modName = str_replace(array("\\","/"), "", $nsPath);

			if(!\Strukt\Fs::isFile(sprintf("%s/%s/%s.php", $srcDir, $nsPath, $modName))){

				echo "\n  Invalid Namespace!\n\n";
				continue;
			}
			
			$namespace = str_replace("/", '\\', $namespace);
			$router["id"]["namespace"] = sprintf("%s\Router", str_replace(array("\router", "\Router"), "", $namespace));
		}

		$prompt["router_name"] = "Router: ";

		while(empty($router["id"]["name"])){

			$router["id"]["name"] = trim($in->getInput($prompt["router_name"]));

			if(empty($router["id"]["name"])){

				$prompt["router_name"] = "Router [REQUIRED!]: ";

				continue;
			}
			
			$router["id"]["name"] = str_replace(array("router", "Router"), "", $router["id"]["name"]);
		}
		
		$router["id"]["extends"] = "\App\Data\Router";

		$continue = true;

		while($continue){

			$prompt = null;
			$method = null;
			$invalid = null;

			$prompt["route"] = " Method Route: ";

			while(empty($method["route"])){

				$method["route"] = trim($in->getInput($prompt["route"]));
				if(empty($method["route"])){

					$prompt["route"] = " Method Route [REQUIRED!]: ";

					continue;
				}

				$method["route"] = sprintf("/%s", trim($method["route"],"/"));
			}

			$prompt["perm"] = " Method Permission (Optional): ";
			$method["perm"] = trim($in->getInput($prompt["perm"]));

			$prompt["method_name"] = " Method Name: ";

			while(empty($method["name"])){

				$method["name"] = trim($in->getInput($prompt["method_name"]));
				if(empty($method["name"]))	
					$prompt["method_name"] = " Method Name [REQUIRED!]: ";
			}
			
			$invalid["params"] = true;
			$prompt["params"] = " Method Parameter(s) separater[,]: ";

			while($invalid["params"]){

				$params = trim($in->getInput($prompt["params"]));

				if(!empty($params)){

					if(!preg_match("/^[\w\s,]+$/", $params)){

						echo "\n  Invalid Input!\n\n";
						$prompt["params"] = " Method Parameter(s) separater[,]: ";

						continue;
					}

					$method["params"] = array_map(function($param){

						return trim($param);

					}, explode(",", trim($params, ",")));
				}

				$invalid["params"] = false;
			}

			$invalid["actions"] = true;
			$prompt["actions"] = " Method HTTP Action(s) separate with[,] (GET): ";

			while($invalid["actions"]){

				$actions = trim($in->getInput($prompt["actions"]));

				$method["actions"] = "GET";

				if(!empty($actions)){

					if(!preg_match("/^(GET|POST|DELETE|,|\s)+$/", $actions)){

						echo "\n  Invalid Input!\n\n";
						$prompt["actions"] = " Method HTTP Action(s) separate with[,] (GET): ";

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

			$continue = trim($in->getInput("Add Method (y): "));
			if(empty($continue))
				$continue = "y";
			
			if($continue == "y")
				continue;
			
			$continue = false;
		}

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