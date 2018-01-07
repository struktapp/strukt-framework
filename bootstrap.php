<?php

use Kambo\Http\Message\Environment\Environment;
use Kambo\Http\Message\Factories\Environment\ServerRequestFactory;
// use Kambo\Http\Message\Stream;
use Kambo\Http\Message\Response;

use Strukt\Fs;
use Strukt\Event\Single;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

$appCfg = parse_ini_file("cfg/app.ini");

$loader = require 'vendor/autoload.php';
$loader->add('App', __DIR__.'/lib/');
// $loader->add('Strukt', __DIR__.'/../strukt-commons/src/');
// $loader->add('Strukt', __DIR__.'/../strukt-router/src/');
$loader->add($appCfg["app-name"], __DIR__.'/app/src/');


$registry = \Strukt\Core\Registry::getInstance();

$registry->set("_dir", __DIR__);

$registry->set("servReq", new Single(function(){

	if(empty($_SERVER["REQUEST_SCHEME"]))
			$_SERVER["REQUEST_SCHEME"] = "http";

	$env = new Environment($_SERVER, fopen('php://input', 'w+'), $_POST, $_COOKIE, $_FILES);

	$servReq = (new ServerRequestFactory())->create($env);

	return $servReq;
}));

foreach(["NotFound"=>404, "MethodNotFound"=>405,
		 	"Forbidden"=>403, "ServerError"=>500,
			"Ok"=>200, "Redirected"=>302] as $msg=>$code)
	$registry->set(sprintf("Response.%s", $msg), new Single(function() use($code){

		$res = new Response($code);

		if(in_array($code, array(403,404,405,500)))
			$res->getBody()->write(Fs::cat(sprintf("public/errors/%d.html", $code)));

		return $res;
	}));