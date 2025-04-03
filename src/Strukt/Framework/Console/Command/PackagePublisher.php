<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Ref;
use Strukt\Package\Repos;


/**
* package:publish     Package Publisher
*
* Usage:
*
*       package:publish <pkg> [--dev]
*
* Arguments:
*
*       pkg   package name
*
* Options:
*
*      --dev -d   For when you are developing a packge in your root/home folder
*/
class PackagePublisher extends \Strukt\Console\Command{

	public function __construct(){

		$this->packages = Repos::available();
	}

	public function execute(Input $in, Output $out){

		$pkgname = $in->get("pkg");

		$vendorPkg = str("");
		if($pkgname == "package"){

			$devpkgname = ds(sprintf("/%s/", $pkgname));
			$vendor_pkg = $vendorPkg->concat($devpkgname)->yield();
		}
		else{

			$vendor_pkg = "./";
			if(negate(array_key_exists("dev", $in->getInputs())))
				$vendor_pkg = $vendorPkg
							->concat(env("vendor_fw"))
							->concat($pkgname)->yield();
		}

		$vendor_pkg = ds($vendor_pkg);

		if(is_null(config("app.name")))
			raise("Unabale to find config[app.name]!");

		/**
		 * @todo [1] use the below line to pass arguments for class PkgAuth(Red) vs. class PkgAuth(Pop)
		 */
		$pkgclass = $this->packages[$pkgname]; 
		if(!class_exists($pkgclass))
			raise(sprintf("Package [%s] is not installed!", $pkgclass));

		$pkg = Ref::create($pkgclass)->make()->getInstance();
		/** @todo [1] */

		$appname = config("app.name");

		$requirements = $pkg->getRequirements();
		
		if(!is_null($requirements)){

			$published = Repos::packages("published");
			foreach($requirements as $requirement)
				if(!in_array($requirement, $published))
					raise(sprintf("Please install and publish package [%s]!", $requirement));
		}

		arr($pkg->getFiles())->each(function($key, $relpath) use ($pkgname, 
																	$vendor_pkg, 
																	$appname){

			/**
			 * @todo [2] use the immediate line below to resolve paths for PkgAuth(Red) vs. PkgAuth(Pop)
			 */ 
			$dbtype = config("package.auth.db");
			$vendor_appbase = str(fs(env("rel_appsrc"))
								->concat($dbtype?sprintf("%s/", $dbtype):"")
								->path("App"))
								->yield();

			$qpath = str($relpath);
			if($qpath->contains($vendor_appbase))
				$qpath = $qpath->replace($vendor_appbase, sprintf("app/src/%s", $appname));

			if($qpath->endsWith(".sgf"))
				$qpath = $qpath->replace(".sgf", ".php");

			$actual_path = $qpath->yield();

			$vendorFilePath = str($vendor_pkg);
			if($pkgname != "package")
				$vendorFilePath = $vendorFilePath->concat("package/");
	
			$vendor_file_path = $vendorFilePath->concat($relpath)->yield();

			$path = pathinfo($actual_path);
			$qfilename = str($path["filename"]);
			if($qfilename->startsWith("_")){

				$filename = $qfilename->replace("_", $appname)->yield();
				$actual_path = $qpath->replace($path["filename"], $filename);
			}

			fs()->mkdir($path["dirname"]);

			$file_content = fs()->cat($vendor_file_path);
			if(str($vendor_file_path)->endsWith(".sgf") &&
				!str($vendor_file_path)->contains(".tpl/sgf"))
					$file_content = template($file_content, array(

						"app"=>$appname
					));

			if(fs()->isFile($actual_path))
				fs()->rename($actual_path, sprintf("%s~", $actual_path));

			fs()->touchWrite($actual_path, $file_content);
		});

		$out->add("Package successfully published\n");
	}
}	