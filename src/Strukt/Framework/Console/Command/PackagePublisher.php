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

		$pkg_name = $in->get("pkg");
		list($pkg_name, $pkg_which) = str($pkg_name)->split(":");
		list($_, $short_name) = str($pkg_name)->split("-");

		$pkg_config = sprintf("package.%s.default", $short_name);
		if(is_null($pkg_which))
			$which = config($pkg_config);

		if(is_null($which) && notnull($pkg_which)){

			reg("config.package.db")->remove("default");
			config($pkg_config, $pkg_which);
		}

		$install_path = $pkg_which?ds($pkg_which):"";
		$vendor_appbase = str(fs(env("rel_appsrc"))->path("App"))
							->prepend($install_path)
							->yield();

		$vendorPkg = str("");
		$vendor_pkg = "./";
		$dev_mode = array_key_exists("dev", $in->getInputs());

		if(negate($dev_mode))
			$vendor_pkg = $vendorPkg
						->concat(env("vendor_fw"))
						->concat($pkg_name)->yield();

		if($dev_mode)
			$vendor_pkg = $vendorPkg->concat(ds("/package/"))->yield();

		$vendor_pkg = ds($vendor_pkg);

		if(is_null(config("app.name")))
			raise("Unabale to find config[app.name]!");

		$pkg_class = $this->packages[$pkg_name]; 
		if(!class_exists($pkg_class))
			raise(sprintf("Package [%s] is not installed!", $pkg_class));

		$pkg = Ref::create($pkg_class)->make()->getInstance();

		/**
		 * Preinstall
		 */
		$pkg->preInstall();

		$app_name = config("app.name");

		$requirements = $pkg->getRequirements();
		
		if(!is_null($requirements)){

			$published = Repos::packages("published");
			foreach($requirements as $requirement)
				if(!in_array($requirement, $published))
					raise(sprintf("Please install and publish package [%s]!", $requirement));
		}

		$bak_dir = ds(sprintf(".bak/%s", today()->format("YmdHis")));
		fs()->mkdir($bak_dir);
		arr($pkg->getFiles())->each(function($key, $relpath) use ($pkg_which,
																	$bak_dir, $pkg_name,
																	$vendor_pkg, $app_name,
																	$vendor_appbase, $dev_mode){

			$qpath = str($relpath);
			if($qpath->contains($vendor_appbase))
				$qpath = $qpath->replace($vendor_appbase, sprintf("app/src/%s", $app_name));

			$actual_path = $qpath->clone();

			$vendorFilePath = str($vendor_pkg);
			if(negate($dev_mode))
				$vendorFilePath = $vendorFilePath->concat("package/");
	
			$vendor_file_path = $vendorFilePath->concat($relpath)->yield();

			$path = pathinfo($qpath);
			$qfilename = str($path["filename"]);
			if($qfilename->startsWith("_")){

				$filename = $qfilename->replace("_", $app_name)->yield();
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
				negate(str($vendor_file_path)->contains(".tpl/sgf")))
					$file_content = template($file_content, array(

						"app"=>$app_name
					));

			/**
			 * Overwrite and back-up files
			 */
			$dir = dirname($actual_path);
			if(notnull($pkg_which))
				if(str($dir)->startsWith($pkg_which))
					$dir = str($dir)->replace(ds($pkg_which),"")->yield();

			$filename = basename($actual_path);
			$fsOut = fs($dir);

			if($fsOut->isFile($filename)){

				fs($bak_dir)->mkdir($dir);
				$fsIn = fs(ds(str($bak_dir)->concat($dir)->yield()));
				$fsIn->touchWrite($filename, $fsOut->cat($filename));
				$fsOut->overwrite($filename, $file_content);
			}

			/**
			 * Write new files
			 */
			if(negate($fsOut->isFile($filename)))
				$fsOut->touchWrite($filename, $file_content);
		});

		/**
		 * Postinstall
		 */ 
		$pkg->postInstall();
		$out->add("Package successfully published\n");
	}
}