<?php

namespace Seed;

use Doctrine\DBAL\Connection;

class User20180626221519{

	/**
	* @param Connection $conn
	*/
	public function up(Connection $conn){

		$users = array(

			array(

				"id"=>1,
				"username"=>"admin",
				"password"=>sha1("p@55w0rd"),
				"role_id"=>1
			),
			array(

				"id"=>2,
				"username"=>"sadmin",
				"password"=>sha1("s@dm1n"),
				"role_id"=>2
			)
		);

		foreach($users as $user)
			$conn->insert("user", $user);

		// print_r($conn->getSql());
	}

	/**
	* @param Connection $conn
	*/
	public function down(Connection $conn){

		$conn->exec("DELETE FROM user;");
	}
}