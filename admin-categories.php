<?php

use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

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

// Exibe página de categoria especificada pelo ID
$app->get("/categories/:idcategory", function($idcategory) {
	$category = new Category();
	$category->get((int)$idcategory);

	$page = new Page();
	$page->setTpl("category", [
		"category"	=>	$category->getValues(),
		"products"	=>	[]
	]);
});