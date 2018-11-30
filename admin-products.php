<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Product;

// Lista dos produtos cadastrados
$app->get("/admin/products", function() {
    User::verifyLogin();

    $products = Product::listAll();

    $page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	3
    )));
    
    $page->setTpl("products", [
        "products"  =>  $products
    ]);
});

// Cadastrar novo produto
$app->get("/admin/products/create", function() {
    User::verifyLogin();

    $page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	3
    )));

    $page->setTpl("products-create");
});

// Salvar produto no banco
$app->post("/admin/products/create", function() {
    User::verifyLogin();

    $product = new Product();
    $product->setData($_POST);
    $product->save();

    header("Location: /admin/products");
    exit();
});

// Exclui produto
$app->get("/admin/products/:idproduct/delete", function($idproduct) {
    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);
    $product->delete();
    
    header("Location: /admin/products");
    exit();
});

// Editar produto
$app->get("/admin/products/:idproduct", function($idproduct) {
    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);
    
    $page = new PageAdmin(array(
		"data"	=>	array(
			"activeLink"	=>	3
    )));

    $page->setTpl("products-update", [
        "product"   =>  $product->getValues()
    ]);
});

// Salva alterações do produto no banco
$app->post("/admin/products/:idproduct", function($idproduct) {
    User::verifyLogin();

    $product = new Product();
    $product->get((int)$idproduct);
    $product->setData($_POST);
    $product->save();

    if (isset($_FILES["file"]) && $_FILES["file"]["name"] != '' && $_FILES["file"]["name"] != NULL)
        $product->setPhoto($_FILES["file"]);

    header("Location: /admin/products");
    exit();
});