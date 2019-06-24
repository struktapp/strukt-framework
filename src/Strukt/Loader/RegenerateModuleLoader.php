<?php 

namespace Strukt\Loader;

use Strukt\Fs;
use Strukt\Env;
use Strukt\Generator\Parser;
use Strukt\Generator\Compiler;

/**
* Helper that generates module loader
*
* @author Moderator <pitsolu@gmail.com>
*/
class RegenerateModuleLoader{

	/**
	* Module loader string
	*
	* @var string
	*/
	private $module_loader_contents;

	/**
	* Constructor
	*
	* Resolve available module and generate class
	*/
	public function __construct(){

		$root_dir = Env::get("root_dir");
		$app_dir = Env::get("rel_appsrc_dir");
		$loader_sgf_file = Env::get("rel_loader_sgf");

		$appsrc_path = sprintf("%s/%s", $root_dir, $app_dir);

		if(!Fs::isPath($appsrc_path))
			throw new \Exception(sprintf("Application source path [%s] does not exist!", 
											$appsrc_path));

		foreach(scandir($appsrc_path) as $srcItem)
			if(!preg_match("/(.\.php|\.)/", $srcItem))
				$apps[] = $srcItem;

		foreach($apps as $app){

			$app_path = sprintf("%s%s", $appsrc_path, $app);

			foreach(scandir($app_path) as $appItem)
				if(!preg_match("/(.\.php|\.)/", $appItem))
					$all[$app][] = $appItem;
		}

		foreach($all as $name=>$mods)
			foreach($mods as $mod)
				$register[] = sprintf("\$this->app->register(new \%s\%s\%s%s());", 
										$name, $mod, $name, $mod);

		if(!Fs::isFile($loader_sgf_file))
			throw new \Exception(sprintf("File [%s] was not found!", $loader_sgf_file));
			
		$sgf_contents = Fs::cat($loader_sgf_file);

		$parser = new Parser($sgf_contents);
		$compiler = new Compiler($parser, array(

			"excludeMethodParamTypes"=>array(

				"string",
				"integer",
				"double",
				"float"
			)
		));

		$result = sprintf("<?php\n%s", $compiler->compile());

		$this->module_loader_contents = sprintf($result, implode("\n\t\t\t", $register));
	}

	/**
	* Render module loader class
	*
	* @return string
	*/
	public function __toString(){

		return $this->module_loader_contents;
	}
}