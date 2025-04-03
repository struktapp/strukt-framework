<?php

namespace Strukt\Framework\Contract;

/**
* @author Moderator <pitsolu@gmail.com>
*/
interface Package{

	public function getName():string;
	public function getModules():array|null;
	public function getFiles():array|null;
	public function isPublished():bool;
	public function getRequirements():array|null;
	public function postInstall():void;
	public function preInstall():void;
}