<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Env;
// use Strukt\Fs;
use Strukt\Process;
use Strukt\Templator as Tpl;

/**
* app:exec     Run application (run)
*
* Usage:
*
*      app:exec [<port>] [--log]
*
* Arguments:
*
*      port  optional: Http port overide 
*
* Options:
*
*      --log -l   Flag to enable logs
*/
class AppExec extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$host = "localhost";
		if(Env::has("serve_host"))
			$host = Env::get("serve_host");

		$ctx = ".";
		if(Env::has("serve_ctx"))
			$ctx = Env::get("serve_ctx");

		$port = $in->get("port"); //port override
		if(empty($port))
			$port = Env::get("serve_port");

		$output = Tpl::create("php -S {{host}}:{{port}} -t {{ctx}}", array(

			"host"=>$host,
			"port"=>$port,
			"ctx"=>$ctx
		));

		$inputs = [];
		if(!is_null($in->getInputs()))
			$inputs = $in->getInputs();

		$channels_switched = false;
		if(array_key_exists("log", $inputs)){

			Process::switchChannels();
			$channels_switched = true;
		}

		if(!$channels_switched)
			echo(sprintf("Served@%s:%s", $host, $port));
		
		$ps = Process::run([$output], function($streamOutput){

			// $match = "#\[.*\] \d+\.\d+\.\d+\.\d+\:\d+#";
			// $log = sprintf("%s\n", trim(preg_replace($match, "", $streamOutput)));
			// if(!in_array($log, ["Accepted", "Closing"]))
				// echo $log;
			
			echo $streamOutput;
		});
	}
}