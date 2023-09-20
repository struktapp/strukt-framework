<?php

namespace Strukt\Framework\Contract;

interface Package{

	public function getName();
	public function getModules();
	public function getFiles();
	public function isPublished();
	public function getRequirements();
}