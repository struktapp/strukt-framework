<?php

namespace Strukt\Framework;

use Strukt\Env;
use Strukt\Console\DocBlockParser;
use Strukt\Framework\Console\Command\AppMake;
use Strukt\Framework\Console\Command\AppReload;
use Strukt\Framework\Console\Command\AppExec;
use Strukt\Framework\Console\Command\MiddlewareMake;
use Strukt\Framework\Console\Command\ProviderMake;
use Strukt\Framework\Console\Command\RouteMake;
use Strukt\Framework\Console\Command\ModuleMake;
use Strukt\Framework\Console\Command\RouteList;
use Strukt\Framework\Console\Command\ShellExec;
use Strukt\Framework\Console\Command\SysUtil;
use Strukt\Framework\Console\Command\SysList;
use Strukt\Framework\Console\Command\CacheMake;
use Strukt\Framework\Console\Command\CacheClear;

/**
* Console Loader
*
* @author Moderator <pitsolu@gmail.com>
*/
class Shell extends \Strukt\Console\Application{

	/**
	* Constructor loads Strukt Framework in-build applications
	* 
	* @param bool $load_native_cmds
	*/
	public function __construct(bool $load_native_cmds = true){

		parent::__construct(env("cli_app_name"), env("cli_file_name"));

		$this->addCmdSect(env("cli_label"));

		if($load_native_cmds){

			$cmds = config("cmd.main.cmd");
			$cmds = arr($cmds);
			
			if($cmds->has("app:make"))$this->add(new AppMake);
			if($cmds->has("app:reload"))$this->add(new AppReload);
			if($cmds->has("app:exec"))$this->add(new AppExec);
			if($cmds->has("middleware:make"))$this->add(new MiddlewareMake);
			if($cmds->has("provider:make"))$this->add(new ProviderMake);
			if($cmds->has("route:make"))$this->add(new RouteMake);
			if($cmds->has("route:ls"))$this->add(new RouteList);
			if($cmds->has("module:make"))$this->add(new ModuleMake);
			if($cmds->has("shell:exec"))$this->add(new ShellExec);
			if($cmds->has("sys:util"))$this->add(new SysUtil);
			if($cmds->has("sys:ls"))$this->add(new SysList);

			if($cmds->has("cache:make") || $cmds->has("cache:clear"))$this->addCmdSect("\nCache");
			if($cmds->has("cache:make"))$this->add(new CacheMake);
			if($cmds->has("cache:clear"))$this->add(new CacheClear);

			$config = new \Strukt\Framework\Configuration();
			$cmds = $config->get("commands");

			$cls = [];
			foreach($cmds as $cmd){

				$doc = new \Strukt\Console\DocBlockParser($cmd);
				$ls = $doc->parse();
				$alias = $ls["command"]["alias"];
				$cls[$alias] = $cmd;
			}

			$cmds = arr(array_flip(config("cmd")->keys()))->each(function($k,$v){

				return config(sprintf("cmd.%s*", $k));
			});

			foreach($cmds->yield() as $key => $val){

				if(array_key_exists("title", $val))
					if(is_string($val["title"]))
						$this->addCmdSect(sprintf("\n%s", $val["title"]));

				if(array_key_exists("cmd", $val))
					if(is_array($val["cmd"]))
						foreach($val["cmd"] as $cmd)
							if(class_exists($cls[$cmd]))
								$this->add(new $cls[$cmd]);
			}
		}
	}
}