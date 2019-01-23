<?php

use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;

// Categorias de produtos
$app->get("/admin/categories", function() {
	User::verifyLogin();

	$search = $_GET['search'] ?? '';
	$pgn = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '')
		$pagination = Category::getPageSearch($search, $pgn);
	else
		$pagination = Category::getPage($pgn);

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
			"activeLink"	=>	2
			)
		)
	);
	$page->setTpl("categories", [
		"categories"=>	$pagination['data'],
		"search"	=>	$search,
		"pages"		=>	$pages
	]);
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

// Lista produtos relacionados à categoria
$app->get("/admin/categories/:idcategory/products", function($idcategory) {
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);

	$page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	2
	)));

	$page->setTpl("categories-products", [
		"category"				=>	$category->getValues(),
		"productsRelated"		=>	$category->getProducts(),
		"productsNotRelated"	=>	$category->getProducts(false)
	]);
});

// Relaciona um produto a uma categoria
$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct) {
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);

	$product = new Product();
	$product->get((int)$idproduct);

	$category->addProduct($product);

	header("Location: /admin/categories/" . $idcategory . "/products");
	exit();
});

// Desrelaciona um produto a uma categoria
$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct) {
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);

	$product = new Product();
	$product->get((int)$idproduct);

	$category->removeProduct($product);

	header("Location: /admin/categories/" . $idcategory . "/products");
	exit();
});