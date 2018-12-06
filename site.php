<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;

// Rota principal (raiz)
$app->get('/', function() {
	$products = Product::listAll();

	$page = new Page();
	$page->setTpl("index", [
		"products"	=>	Product::checkProductsList($products)
	]);
});

// Exibe página de categoria especificada pelo ID
$app->get("/categories/:idcategory", function($idcategory) {
	$category = new Category();
	$category->get((int)$idcategory);

	$page = new Page();
	$page->setTpl("category", [
		"category"	=>	$category->getValues(),
		"products"	=>	Product::checkProductsList($category->getProducts())
	]);
});