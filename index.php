<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app = new Slim();
$app->config('debug', true);

// Rota principal (raiz)
$app->get('/', function() {
	$page = new Page();
	$page->setTpl("index");
});

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

// Lista dos usuários administradores
$app->get('/admin/users', function() {
	User::verifyLogin();

	$users = User::listAll();

	$page = new PageAdmin();
	$page->setTpl("users", array(
		"users"	=>	$users
	));
});

// Criação de novos usuários administradores
$app->get('/admin/users/create', function() {
	User::verifyLogin();

	$page = new PageAdmin();
	$page->setTpl("users-create");
});

// Exclui do banco usuários administradores
$app->get("/admin/users/:iduser/delete", function($iduser) {
	User::verifyLogin();

	$user = new User();
	$user->get((int)$iduser);
	$user->delete();

	header("Location: /admin/users");
	exit();
});

// Atualização de dados de usuários administradores
$app->get('/admin/users/:iduser', function($iduser) {
	User::verifyLogin();

	$user = new User();
	$user->get((int)$iduser);

	$page = new PageAdmin();
	$page->setTpl("users-update", array(
		"user"	=>	$user->getValues()
	));
});

// Salva no banco novos usuários administradores
$app->post("/admin/users/create", function() {
	User::verifyLogin();

	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;

	$user = new User();
	$user->setData($_POST);
	$user->save();

	header("Location: /admin/users");
	exit();
});

// Salva no banco atualização de dados de usuários administradores
$app->post("/admin/users/:iduser", function($iduser) {
	User::verifyLogin();

	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;

	$user = new User();
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->update();

	header("Location: /admin/users");
	exit();
});

// Executa Slim Framework com todas as rodas definidas
$app->run();

?>