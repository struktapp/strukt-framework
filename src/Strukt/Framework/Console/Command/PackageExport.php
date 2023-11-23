<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;

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

		try{

			$fsRoot = fs();
			$fsRoot->mkdir($pkg_fldr);

			$fsPkg = fs($pkg_fldr);
			$fsPkg->mkdir("package");
			$fsPkg->mkdir("src");

			$name = ucfirst(str($name)->toCamel()->yield());
			$fsRoot->cpr("package", $fsPkg->path("package"));

			$pkg_dir_cls = str($fsPkg->path("src"))->concat("/")->concat("Strukt/Package")->yield();
			$source_pkg_cls = sprintf("src/Strukt/Package/Pkg%s.php", $name);
			$dest_pkg_cls = sprintf("%s/Pkg%s.php", $pkg_dir_cls, $name);
			
			$fsRoot->mkdir($pkg_dir_cls);
			$fsRoot->cpr($source_pkg_cls, $dest_pkg_cls);
			$fsRoot->cpr(".env", $fsPkg->path(".env"));
			$fsRoot->cpr(".gitignore", $fsPkg->path(".gitignore"));

			$fsPkg->touchWrite("composer.json", json($composer)->encode());
			$fsPkg->touchWrite("README.md", implode("\n", array(

				sprintf("Strukt %s", ucfirst($name)),
				"==="
			)));

			$out->add(sprintf("Files moved to [pkg-%s]!", str($name)->toSnake()->yield()));
		}
		catch(\Exception $e){

			raise($e->getMessage());
		}
	}
}