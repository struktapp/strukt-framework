<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Core\Registry;
use Strukt\Type\Str;
use LucidFrame\Console\ConsoleTable;
use Strukt\Cmd;

/**
* route:ls     Route List
*
* Usage:
*
*      route:ls [<filter>]
*
* Arguments:
*
*      filter     optional: criteria for filtering routes
*/
class RouteList extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$configs = reg("route.configs");
		$configs = arr(array_flip($configs->keys()))->each(fn($k,$v)=>$configs->get($k));

		$routes = $configs->each(function($route, $permission){

			$route = token($route);
			$permission = token($permission);
			
			return [

				"method"=>$route->get("action"),
				"pattern"=>$route->get("path"),
				"permission"=>$permission->get("allows"),
			];
		});

		// dd($routes);

		$filter = $in->get("filter");

		$table = new ConsoleTable();
		$table->setHeaders(array('Method', 'Route', "Permission"));

		$noRows = true;
		foreach($routes->yield() as $route){

			if(!empty($filter)){
				
				$pattern = Str::create($route["pattern"]);
				if(!$pattern->contains($filter))
					continue;
			}

			$noRows = false;
			$table->addRow(array(

				$route["method"],
				$route["pattern"], 
				$route["permission"], 
			));
		}
		
		if(!$noRows)
			$table->setIndent(1)->display();
	}
}