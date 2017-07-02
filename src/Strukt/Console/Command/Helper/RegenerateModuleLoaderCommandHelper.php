<?php 

namespace Strukt\Console\Command\Helper;

/**
* Helper that generates module loader
*
* @author Moderator <pitsolu@gmail.com>
*/
class RegenerateModuleLoaderCommandHelper{

	/**
	* Module loader string
	*
	* @var string
	*/
	private $moduleLoaderContents;

	/**
	* Constructor
	*
	* Resolve available module and generate class
	*/
	public function __construct(){

		$rootDir = \Strukt\Console::getRootDir();
		if(empty($rootDir))
			throw new \Exception("Strukt root dir not defined! Use Strukt\Console::useRootDir(<root_dir>)");

		$_appDir = \Strukt\Console::getAppDir();
		if(empty($_appDir))
			throw new \Exception("Strukt app dir not defined! Use Strukt\Console::useAppDir(<app_dir>)");

		$srcDir = sprintf("%s/%s/src", $rootDir, $_appDir);

		if(!\Strukt\Fs::isPath($srcDir))
			throw new \Exception("Application source path [$src] does not exist!");

		foreach(scandir($srcDir) as $srcItem)
			if(!preg_match("/(.\.php|\.)/", $srcItem))
				$apps[] = $srcItem;

		foreach($apps as $app){

			$appDir = sprintf("%s/%s", $srcDir, $app);
			foreach(scandir($appDir) as $appItem)
				if(!preg_match("/(.\.php|\.)/", $appItem))
					$all[$app][] = $appItem;
		}

		foreach($all as $name=>$mods)
			foreach($mods as $mod)
				$register[] = sprintf("\$this->app->register(new \%s\%s\%s%s());", $name, $mod, $name, $mod);

		$sgfFile = "tpl/sgf/lib/App/Loader.sgf";
		if(!\Strukt\Fs::isFile($sgfFile))
			throw new \Exception(sprintf("File [%s] was not found!", $sgfFile));
			
		$sgfContents = \Strukt\Fs::cat($sgfFile);
		$parser = new \Strukt\Generator\Parser($sgfContents);
		$compiler = new \Strukt\Generator\Compiler($parser, array(

			"excludeMethodParamTypes"=>array(

				"string",
				"integer",
				"double",
				"float"
			)
		));

		$result = sprintf("<?php\n%s", $compiler->compile());

		$this->moduleLoaderContents = sprintf($result, implode("\n\t\t\t", $register));
	}

	/**
	* Render module loader class
	*
	* @return string
	*/
	public function __toString(){

		return $this->moduleLoaderContents;
	}
}