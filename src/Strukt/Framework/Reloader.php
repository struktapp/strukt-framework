<?php 

namespace Strukt\Framework;

use Strukt\Fs;
// use Strukt\Generator\Parser;
// use Strukt\Generator\Compiler\Runner as Compiler;
// use Strukt\Generator\Compiler\Configuration;
use Strukt\Templator;

/**
* Helper that generates module loader
*
* @author Moderator <pitsolu@gmail.com>
*/
class Reloader{

	/**
	* Module loader string
	*
	* @var string
	*/
	private $loader_output;

	/**
	* Constructor
	*
	* Resolve available module and generate class
	*/
	public function __construct(){

		$root_dir = env("root_dir");
		$app_dir = env("rel_appsrc");
		$loader_sgf_file = env("rel_loader_sgf");

		$appsrc_path = sprintf("%s/%s", $root_dir, $app_dir);
		if(!Fs::isPath($appsrc_path))
			throw new \Exception(sprintf("Application source path [%s] does not exist!", 
											$appsrc_path));

		foreach(scandir($appsrc_path) as $srcItem)
			if(!preg_match("/(.\.php|\.)/", $srcItem))
				$apps[] = $srcItem;

		foreach($apps as $app)
			foreach(scandir($app_path = sprintf("/%s%s", ds($appsrc_path), $app)) as $appItem)
				if(!preg_match("/(.\.php|\.)/", $appItem))
					$all[$app][] = $appItem;

		foreach($all as $name=>$mods)
			foreach($mods as $mod)
				if(preg_match("/[A-Za-z]+Module$/", $mod))
					$register[] = sprintf("\$this->app->register(new \%s\%s\%s%s());", 
											$name, $mod, $name, $mod);

		if(!Fs::isFile($loader_sgf_file))
			raise(sprintf("File [%s] was not found!", $loader_sgf_file));
			
		$tpl_file = Fs::cat($loader_sgf_file);
		$this->loader_output = Templator::create($tpl_file, array(

			"packages"=>implode("\n\t\t\t", $register)
		));
	}

	/**
	* Render module loader class
	*
	* @return string
	*/
	public function __toString(){

		return $this->loader_output;
	}
}