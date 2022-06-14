<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Fs;
use Strukt\Type\Str;
use Strukt\Type\Json;
use Strukt\Env;

/**
* package:export  Export package to another folder
* 
* Usage:
*	
*      package:export <as_name>
*
* Arguments:
*
*      as_name     Folder to export package
*/
class PackageExport extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$as_name = $in->get("as_name");

		$pkg_fldr = sprintf("pkg-%s", $as_name);

		Fs::mkdir($pkg_fldr);
		Fs::mkdir(sprintf("%s/package", $pkg_fldr));
		Fs::mkdir(sprintf("%s/src", $pkg_fldr));

		Fs::cpr("package", sprintf("%s/package", $pkg_fldr));
		Fs::cpr("src", sprintf("%s/src", $pkg_fldr));
		copy("composer.json", sprintf("%s/composer.json", $pkg_fldr));
		copy(".env", sprintf("%s/.env", $pkg_fldr));
		copy(".gitignore", sprintf("%s/.gitignore", $pkg_fldr));
		Fs::touchWrite(sprintf("%s/README.md", $pkg_fldr), implode("\n", array(

			sprintf("Strukt %s", ucfirst($as_name)),
			"==="
		)));
	}
}