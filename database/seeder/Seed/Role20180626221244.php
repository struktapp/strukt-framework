<?php

namespace Seed;

use Doctrine\DBAL\Connection;

class Role20180626221244{

	/**
	* @param Connection $conn
	*/
	public function up(Connection $conn){

		$roles = array(

			array(

				"id"=>1,
				"name"=>"admin",
				"descr"=>"N/A"
			),
			array(

				"id"=>2,
				"name"=>"superadmin",
				"descr"=>"N/A"
			),
			array(

				"id"=>3,
				"name"=>"user",
				"descr"=>"N/A"
			)
		);

		foreach($roles as $role)
			$conn->insert("role", $role);
	}

	/**
	* @param Connection $conn
	*/
	public function down(Connection $conn){

		$conn->exec("DELETE FROM role;");
	}
}