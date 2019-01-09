<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

// Ambiente dos usuários administradores
$app->get('/admin', function() {
	User::verifyLogin();

	$page = new PageAdmin();
	$page->setTpl("index");
});

// Login dos usuários administradores (entrada de dados)
$app->get('/admin/login', function() {
	$page = new PageAdmin([
		"header"	=>	false,
		"footer"	=>	false
	]);
	$page->setTpl("login");
});

//Login dos usuários administradores (autenticação)
$app->post('/admin/login', function() {
	User::login($_POST["login"], $_POST["password"]);
	header("Location: /admin");
	exit();
});

// Logout dos usuários administradores
$app->get('/admin/logout', function() {
	User::logout();
	header("Location: /admin/login");
	exit();
});

// Esqueci a senha (entrada de dados)
$app->get("/admin/forgot", function() {
	$page = new PageAdmin([
		"header"	=>	false,
		"footer"	=>	false
	]);

	$page->setTpl("forgot");
});

// Esqueci a senha (tratamento)
$app->post("/admin/forgot", function() {
	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit();
});

// Esqueci a senha (e-mail enviado)
$app->get("/admin/forgot/sent", function() {
	$page = new PageAdmin([
		"header"	=>	false,
		"footer"	=>	false
	]);

	$page->setTpl("forgot-sent");
});

// Esqueci a senha (redefinindo senha)
$app->get("/admin/forgot/reset", function() {
	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([
		"header"	=>	false,
		"footer"	=>	false
	]);

	$page->setTpl("forgot-reset", array(
		"name"	=>	$user["desperson"],
		"code"	=>	$_GET["code"]
	));
});

// Esqueci a senha (salvando nova senha)
$app->post("/admin/forgot/reset", function() {
	$forgot = User::validForgotDecrypt($_POST["code"]);
	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();
	$user->get((int)$forgot["iduser"]);
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, ["cost" => 12]);
	$user->setPassword($password);

	$page = new PageAdmin([
		"header"	=>	false,
		"footer"	=>	false
	]);

	$page->setTpl("forgot-reset-success");
});