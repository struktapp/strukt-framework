<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Fs;
use Strukt\Templator;
use Strukt\Ref;
use Strukt\Package\Repos;

/**
* package:publish     Package Publisher
*
* Usage:
*
*       package:publish <pkg>
*
* Arguments:
*
*       pkg   package name
*/
class PackagePublisher extends \Strukt\Console\Command{

	public function __construct(){

		$this->packages = Repos::available();
	}

	public function execute(Input $in, Output $out){

		$pkgname = $in->get("pkg");

		$vendorPkg = str(env("root_dir"));
		
		if($pkgname == "package"){

			$devpkgname = Fs::ds(sprintf("/%s/", $pkgname));

			$vendor_pkg = $vendorPkg->concat($devpkgname)->yield();
		}
		else{

			$vendor_pkg = $vendorPkg
							->concat(Fs::ds(env("vendor_fw")))
							->concat($pkgname)->yield();
		}

		if(is_null(config("app.name")))
			raise("cfg/app.ini[app-name] is not defined!");

		$pkgclass = $this->packages[$pkgname]; 

		if(!class_exists($pkgclass))
			raise(sprintf("Package %s is not installed!", $pkgclass));

		$pkg = Ref::create($pkgclass)->make()->getInstance();

		$appname = config("app.name");

		$requirements = $pkg->getRequirements();
		
		if(!is_null($requirements)){

			$published = Repos::packages("published");
			foreach($requirements as $requirement)
				if(!in_array($requirement, $published))
					raise(sprintf("Please install and publish package [%s]!", $requirement));
		}

		Arr::create($pkg->getFiles())->each(function($key, $relpath) use (
			$pkgname, $vendor_pkg, $appname){

			$vendor_appbase = Fs::ds(str(env("rel_appsrc_dir"))
										->concat("App")
										->yield());

			$qpath = str(env("root_dir"))
				->concat(DS)
				->concat(Fs::ds($relpath));

			if($qpath->contains($vendor_appbase))
				$qpath = $qpath->replace(Fs::ds($vendor_appbase), 
											Fs::ds(sprintf("app/src/%s", $appname)));

			if($qpath->endsWith(".sgf"))
				$qpath = $qpath->replace(".sgf", ".php");

			$actual_path = $qpath->yield();

			$vendorFilePath = str($vendor_pkg);

			if($pkgname != "package")
				$vendorFilePath = $vendorFilePath->concat(Fs::ds("/package/"));

			$vendor_file_path = $vendorFilePath->concat($relpath)->yield();

			$path = pathinfo($actual_path);

			$qfilename = str($path["filename"]);
			if($qfilename->startsWith("_")){

				$filename = $qfilename->replace("_", $appname)->yield();
				$actual_path = $qpath->replace($path["filename"], $filename);
			}

			Fs::mkdir($path["dirname"]);

			$file_content = Fs::cat($vendor_file_path);
			if(str($vendor_file_path)->endsWith(".sgf") &&
				!str($vendor_file_path)->contains(Fs::ds(".tpl/sgf")))
					$file_content = Templator::create($file_content, array(

						"app"=>$appname
					));

			if(Fs::isFile($actual_path))
				Fs::rename($actual_path, sprintf("%s~", $actual_path));

			Fs::touchWrite($actual_path, $file_content);
		});

		$out->add("Package successfully published\n");
	}
}	