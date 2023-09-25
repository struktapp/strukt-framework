<?php

namespace Strukt\Framework\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
// use Strukt\Framework\App as FrameworkApp;
use Strukt\Console\Color;
use Strukt\Ref;
use Strukt\Package\Repos;

/**
* package:info  Information on packages
* 
* Usage:
*
*      package:info <name> [--files]
*
* Arguments:
*
*      name     Package name
*
* Options:
*
*      --files -f   Flag to list files
*/
class PackageInfo extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$packages = Repos::available();
		$installed = Repos::packages("installed");

		$name = $in->get("name");
		if($name!="core")
			if(!array_key_exists($name, $packages))
				new \Strukt\Raise(sprintf("Package [%s] does not exist!", $name));

		if(!in_array($name, $installed))
			new \Strukt\Raise(sprintf("Package [%s] is not installed!", $name));

		$cls = $packages[$name];
		if(class_exists($cls))
			$pkg = Ref::create($cls)->make()->getInstance();

		$name = $pkg->getName();
		$is_pub = $pkg->isPublished();
		$req = $pkg->getRequirements();
		$files = $pkg->getFiles();
		$modules = $pkg->getModules();

		$out->add(sprintf("Name: %s\n", $name));
		$out->add(sprintf("Published: %s\n", [
			Color::write("red", "False"), 
			Color::write("green", "True")][$is_pub]));

		if(!empty($req))
			$out->add(sprintf("Requirements: %s\n", implode("\n", $req)));

		if(!empty($modules))
			$out->add(sprintf("Modules: %s\n", implode("\n", $modules)));

		$out->add("Settings:");
		foreach(["App:Idx", "App:Cli"] as $type){

			$out->add(sprintf("\n Type: %s", Color::write("blue", $type)));
			$settings = $pkg->getSettings($type);
			if(empty($settings))
				$out->add(sprintf(":%s", Color::write("green","None")));

			foreach(["commands", "middleware", "providers"] as $facet){

				if(!array_key_exists($facet, $settings))
					continue;

				$classes = $settings[$facet];

				if(!empty($classes)){

					$out->add(Color::write("green", sprintf("\n  Facet: %s\n", ucfirst($facet))));
					$out->add(Color::write("yellow", "   ".implode("\n   ", $classes)));
				}
			}
		}

		if(!empty($files))
			if(array_key_exists("files", $in->getInputs()))
				$out->add(sprintf("\nFiles:\n   %s\n", 
									Color::write("yellow", implode("\n   ", $files))));
	}
}