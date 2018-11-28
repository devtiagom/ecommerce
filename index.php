<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

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

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	1
	)));
	$page->setTpl("users", array(
		"users"	=>	$users
	));
});

// Criação de novos usuários administradores
$app->get('/admin/users/create', function() {
	User::verifyLogin();

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	1
	)));
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

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	1
	)));
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

// Rota - esqueci a senha (entrada de dados)
$app->get("/admin/forgot", function() {
	$page = new PageAdmin([
		"header"	=>	false,
		"footer"	=>	false
	]);

	$page->setTpl("forgot");
});

// Rota - esqueci a senha (tratamento)
$app->post("/admin/forgot", function() {
	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit();
});

// Rota - esqueci a senha (e-mail enviado)
$app->get("/admin/forgot/sent", function() {
	$page = new PageAdmin([
		"header"	=>	false,
		"footer"	=>	false
	]);

	$page->setTpl("forgot-sent");
});

// Rota - esqueci a senha (redefinindo senha)
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

// Rota - esqueci a senha (salvando nova senha)
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

// Categorias de produtos
$app->get("/admin/categories", function() {
	$categories = Category::listAll();

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	2
	)));
	$page->setTpl("categories", ["categories" => $categories]);
});

// Criar nova categoria
$app->get("/admin/categories/create", function() {
	User::verifyLogin();

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	2
	)));

	$page->setTpl("categories-create");
});

// Salvar no banco nova categoria
$app->post("/admin/categories/create", function() {
	User::verifyLogin();

	$category = new Category();
	$category->setData($_POST);
	$category->save();

	header("Location: /admin/categories");
	exit();
});

// Exclui categoria do banco
$app->get("/admin/categories/:idcategory/delete", function($idcategory) {
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);
	$category->delete();

	header("Location: /admin/categories");
	exit();
});

// Atualiza categoria no banco
$app->get("/admin/categories/:idcategory", function($idcategory) {
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	2
	)));

	$page->setTpl("categories-update", [
		"category"	=>	$category->getValues()
	]);
});

// Salva atualização de categoria no banco
$app->post("/admin/categories/:idcategory", function($idcategory) {
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);
	$category->setData($_POST);
	$category->save();

	header("Location: /admin/categories");
	exit();
});

// Executa Slim Framework com todas as rodas definidas
$app->run();

?>