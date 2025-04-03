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

		list($_, $short_name) = str($pkgname)->split("-");

		$which_pkg = config(sprintf("package.%s.default", $short_name));
		$install_path = $which_pkg?ds($which_pkg):"";
		$vendor_appbase = str(fs(env("rel_appsrc"))->path("App"))
							->prepend($install_path)
							->yield();

		$vendorPkg = str("");
		$vendor_pkg = "./";
		$isDevMode = array_key_exists("dev", $in->getInputs());

		if(negate($isDevMode))
			$vendor_pkg = $vendorPkg
						->concat(env("vendor_fw"))
						->concat($pkgname)->yield();

		if($isDevMode)
			$vendor_pkg = $vendorPkg->concat(ds("/package/"))->yield();

		$vendor_pkg = ds($vendor_pkg);

		if(is_null(config("app.name")))
			raise("Unabale to find config[app.name]!");

		$pkgclass = $this->packages[$pkgname]; 
		if(!class_exists($pkgclass))
			raise(sprintf("Package [%s] is not installed!", $pkgclass));

		$pkg = Ref::create($pkgclass)->make()->getInstance();

		/**
		 * Preinstall
		 */
		$pkg->preInstall();

		$appname = config("app.name");

		$requirements = $pkg->getRequirements();
		
		if(!is_null($requirements)){

			$published = Repos::packages("published");
			foreach($requirements as $requirement)
				if(!in_array($requirement, $published))
					raise(sprintf("Please install and publish package [%s]!", $requirement));
		}

		\Strukt\Fs::mkdir(".bak");
		arr($pkg->getFiles())->each(function($key, $relpath) use ($pkgname, 
																	$vendor_pkg, 
																	$appname,
																	$vendor_appbase,
																	$isDevMode){

			$qpath = str($relpath);
			if($qpath->contains($vendor_appbase))
				$qpath = $qpath->replace($vendor_appbase, sprintf("app/src/%s", $appname));

			$actual_path = $qpath->clone();

			$vendorFilePath = str($vendor_pkg);
			if(negate($isDevMode))
				$vendorFilePath = $vendorFilePath->concat("package/");
	
			$vendor_file_path = $vendorFilePath->concat($relpath)->yield();

			$path = pathinfo($qpath);
			$qfilename = str($path["filename"]);
			if($qfilename->startsWith("_")){

				$filename = $qfilename->replace("_", $appname)->yield();
				$actual_path = $qpath->replace($path["filename"], $filename);
			}

			if($actual_path->endsWith(".sgf"))
				$actual_path = $actual_path->replace(".sgf", ".php");

			$actual_path = $actual_path->yield();

			fs()->mkdir($path["dirname"]);

			/**
			 * Get new file contents
			 */
			$file_content = fs()->cat($vendor_file_path);
			if(str($vendor_file_path)->endsWith(".sgf") &&
				!str($vendor_file_path)->contains(".tpl/sgf"))
					$file_content = template($file_content, array(

						"app"=>$appname
					));

			/**
			 * Back-up files
			 */
			$dir = dirname($actual_path);
			$filename = basename($actual_path);
			$fsOut = fs($dir);
			if($fsOut->isFile($actual_path)){

				fs(".bak")->mkdir($dir);
				$fsIn = fs(ds(str(".bak/")->concat($dir)->yield()));
				$fsIn->touchWrite($filename, $fsOut->cat($filename));
				// fs()->rename($actual_path, sprintf("%s~", $actual_path));
			}

			/**
			 * Write new files
			 */
			$fsOut->touchWrite($filename, $file_content);
		});

		/**
		 * Postinstall
		 */ 
		$pkg->postInstall();
		$out->add("Package successfully published\n");
	}
}