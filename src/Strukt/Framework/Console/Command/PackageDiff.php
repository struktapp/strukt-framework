<?php declare(strict_types=1);

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Package\Repos;
use Strukt\Ref;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
* package:diff  Diff only for package dev-mode
* 
* Usage:
*	
*      package:diff [<type>]
*
* Arguments:
*
*      type     optional: (full|short|min) default:min
*/
class PackageDiff extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$type = $in->get("type");
		if(is_null($type))
			$type = "min";

		if(negate(in_array($type, ["full","short", "min"])))
			raise("arg[type] must be either (full|short|min)!");

		$packages = Repos::available();
		$installed = Repos::packages("installed");

		$path = @array_shift(\Strukt\Fs::lsr(ds("./src/Strukt/Package")));
		$name = str(basename($path))
					->replace(".php","")
					->toSnake()
					->replace("_","-")
					->yield();

		if(str($name)->notEquals("core"))
			if(negate(array_key_exists($name, $packages)))
				raise(sprintf("package[%s] does not exist!", $name));

		if(negate(in_array($name, $installed)))
			raise(sprintf("package[%s] is not installed!", $name));

		$class = $packages[$name];
		if(class_exists($class))
			$pkg = Ref::create($class)->make()->getInstance();

		$app_name = config("app.name");
		if(is_null($app_name))
			raise("Run [app:make <app_name>] and [app:reload] to generate you app!");

		if(negate(class_exists(Differ::class)))
			raise("cmd[package:diff] requires package[sebastian/diff:^7.0@dev]!");

		$differ = null;
		$type = str($type);
		if($type->equals("full"))
			$differ = new Differ(new UnifiedDiffOutputBuilder);

		$files = arr($pkg->getFiles())->each(function($_, $file) use($app_name, $differ, $type){

			$ofile = str(ds("package"))->concat($file)->yield();

			$slash_app_name = ds(sprintf("/%s", $app_name));
			if(str($file)->contains("Module.sgf"))
				$file = str($file)->replace("_", $app_name)->yield();

			$nfile = str($file)->replace(".sgf", ".php");
			if(negate($nfile->startsWith(ds("lib/App"))))
				$nfile = $nfile->replace(ds("/App/"), $slash_app_name);

			$nfile = $nfile->yield();

			$fs = fs();
			$ncontents = $fs->cat($nfile);
			$ocontents = str($fs->cat($ofile))->replace("{{app}}", $app_name)->yield();

			$ohash = md5($ocontents);
			$nhash = md5($ncontents);
			if(negate(str($ohash)->equals($nhash))){

				if($type->equals("min"))
					print_r(sprintf("%s\n", $nfile));
				
				if(in_array($type->yield(), ["full", "short"])){

					print_r(sprintf("---%s\n+++%s\n\n", $ofile, $nfile));
					if(notnull($differ))
						print_r($differ->diff($ocontents, $ncontents));
				}
			}
		});
	}
}