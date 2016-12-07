<?php

class RegistryTest extends PHPUnit_Framework_TestCase{

	public function testRegistry(){

		$r = \Strukt\Framework\Registry::getInstance();
		$r->set("user.firstname", "Donald");
		$r->set("user.surname", "Trump");

		$u = $r->get("user");
		$c = \Strukt\Builder\CollectionBuilder::getInstance(new \Strukt\Core\Collection($u->getName()))
			->fromAssoc(array(

				"firstname"=>"Donald",
				"surname"=>"Trump"
			));

		$this->assertEquals($r->get("user.firstname"), "Donald");
		$this->assertEquals($c, $u);
	}

	public function testPersistence(){

		$r = \Strukt\Framework\Registry::getInstance();
		
		$this->assertEquals($r->get("user.surname"), "Trump");
	}
}