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

		$routes = arr(Cmd::ls("^type:route"))->each(function($key, $route){

			$token = token($route);
			return [

				"method"=>$token->get("action"),
				"pattern"=>$token->get("path"),
				"permission"=>$token->get("permission"),
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