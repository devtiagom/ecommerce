<?php

use \Hcode\Page;
use \Hcode\Model\Product;

// Rota principal (raiz)
$app->get('/', function() {
	$products = Product::listAll();

	$page = new Page();
	$page->setTpl("index", [
		"products"	=>	Product::checkProductsList($products)
	]);
});