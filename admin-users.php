<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

// Alterar senha de usuário administrador (entrar com os dados)
$app->get("/admin/users/:iduser/password", function($iduser) {
	User::verifyLogin();

	$user = new User();
	$user->get((int)$iduser);

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	1
			)
		)
	);
	$page->setTpl("users-password", [
		"user"		=>	$user->getValues(),
		"msgError"	=>	User::getError(),
		"msgSuccess"=>	User::getSuccess()
	]);
});

// Alterar senha de usuário administrador (salvar no banco)
$app->post("/admin/users/:iduser/password", function($iduser) {
	User::verifyLogin();

	if (!isset($_POST['despassword']) || $_POST['despassword'] === '') {
		User::setError("Digite a nova senha.");
		header('Location: /admin/users/' . $iduser . '/password');
		exit();
	}

	if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === '') {
		User::setError("Confirme a nova senha.");
		header('Location: /admin/users/' . $iduser . '/password');
		exit();
	}

	if ($_POST['despassword'] !== $_POST['despassword-confirm']) {
		User::setError("A nova senha e a confirmação devem ser iguais.");
		header('Location: /admin/users/' . $iduser . '/password');
		exit();
	}

	$user = new User();
	$user->get((int)$iduser);
	$user->setPassword(User::getPasswordHash($_POST['despassword']));

	User::setSuccess("Senha atualizada com sucesso!");
	header('Location: /admin/users/' . $iduser . '/password');
	exit();
});

// Lista dos usuários
$app->get('/admin/users', function() {
	User::verifyLogin();

	$search = $_GET['search'] ?? '';
	$pgn = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '')
		$pagination = User::getPageSearch($search, $pgn);
	else
		$pagination = User::getPage($pgn);

	$pages = [];
	for ($i = 0; $i < $pagination['pages']; $i ++) {
		array_push($pages, [
			'href'	=>	'/admin/users?' . http_build_query([
				'page'	=>	$i + 1,
				'search'=>	$search
			]),
			'text'	=>	$i + 1
		]);
	}

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	1
			)
		)
	);
	$page->setTpl("users", array(
		"users"	=>	$pagination['data'],
		"search"=>	$search,
		"pages"	=>	$pages
	));
});

// Criação de novos usuários
$app->get('/admin/users/create', function() {
	User::verifyLogin();

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	1
	)));
	$page->setTpl("users-create");
});

// Exclui do banco usuários
$app->get("/admin/users/:iduser/delete", function($iduser) {
	User::verifyLogin();

	$user = new User();
	$user->get((int)$iduser);
	$user->delete();

	header("Location: /admin/users");
	exit();
});

// Atualização de dados de usuários
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

// Salva no banco novos usuários
$app->post("/admin/users/create", function() {
	User::verifyLogin();

	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;

	$user = new User();
	$user->setData($_POST);
	$user->save();

	header("Location: /admin/users");
	exit();
});

// Salva no banco atualização de dados de usuários
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