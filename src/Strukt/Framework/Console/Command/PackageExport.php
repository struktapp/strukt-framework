<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
// use Strukt\Fs;
// use Strukt\Type\Str;
// use Strukt\Type\Json;
// use Strukt\Env;

/**
* package:export  Export package
* 
* Usage:
*	
*      package:export <name>
*
* Arguments:
*
*      name     New folder name
*/
class PackageExport extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$name = $in->get("name");
		$pkg_fldr = sprintf("pkg-%s", $name);

		$composer = array(

		    "name"=>sprintf("strukt/%s", $pkg_fldr),
		    "description"=>"N/A",
		    "type"=>"strukt-module",
		    "require"=>array(),
		    "require-dev"=>array(
		        "strukt/framework"=>"dev-master"
		    ),
		    "license"=>"MIT",
		    "autoload"=>array(
		        "psr-0"=>array(
		            "Strukt\\"=>"src/"
		        )
		    ),
		    "authors"=>array(
		        array(
		            "name"=>"pitsolu",
		            "email"=>"pitsolu@gmail.com"
		        )
		    ),
		    "minimum-stability"=>"dev"
		);

		$fsRoot = fs();
		$fsRoot->mkdir($pkg_fldr);

		$fsPkg = fs($pkg_fldr);
		$fsPkg->mkdir("package");
		$fsPkg->mkdir("src");

		$fsRoot->cpr("../package", "package");
		$fsRoot->cpr("../src", "src");
		$fsRoot->cpr("../.env", ".env");
		$fsRoot->cpr("../.gitignore", ".gitignore");

		$fsPkg->touchWrite("composer.json", Json::pp($composer));
		$fsPkg->touchWrite("README.md", implode("\n", array(

			sprintf("Strukt %s", ucfirst($name)),
			"==="
		)));

		// Fs::mkdir($pkg_fldr);
		// Fs::mkdir(sprintf("%s/package", $pkg_fldr));
		// Fs::mkdir(sprintf("%s/src", $pkg_fldr));

		// Fs::cpr("package", sprintf("%s/package", $pkg_fldr));
		// Fs::cpr("src", sprintf("%s/src", $pkg_fldr));
		// copy("composer.json", sprintf("%s/composer.json", $pkg_fldr));
		// copy(".env", sprintf("%s/.env", $pkg_fldr));
		// copy(".gitignore", sprintf("%s/.gitignore", $pkg_fldr));
		// Fs::touchWrite(sprintf("%s/composer.json", $pkg_fldr), Json::pp($composer));
		// Fs::touchWrite(sprintf("%s/README.md", $pkg_fldr), implode("\n", array(

			// sprintf("Strukt %s", ucfirst($as_name)),
			// "==="
		// )));
	}
}