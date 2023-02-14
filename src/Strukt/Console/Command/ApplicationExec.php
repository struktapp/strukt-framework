<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Env;
// use Strukt\Fs;
use Strukt\Process;
use Strukt\Templator as Tpl;

/**
* app:exec     Run application
*/
class ApplicationExec extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$host = Env::get("serve_host");
		$port = Env::get("serve_port");
		$ctx = Env::get("serve_ctx");

		echo(sprintf("Served@%s:%s", $host, $port));

		$output = Tpl::create("php -S {{host}}:{{port}} -t {{ctx}}", array(

			"host"=>$host,
			"port"=>$port,
			"ctx"=>$ctx
		));

		$ps = Process::run([$output], function($streamOutput){

			echo $streamOutput;
		});
	}
}