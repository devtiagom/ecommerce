<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;

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
	$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

	$category = new Category();
	$category->get((int)$idcategory);
	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i = 1; $i <= $pagination["pages"]; $i ++)
		array_push($pages, [
			'link'	=>	'/categories/' . $category->getidcategory() . '?page=' . $i,
			'page'	=> $i
		]);

	$page = new Page();
	$page->setTpl("category", [
		"category"	=>	$category->getValues(),
		"products"	=>	$pagination["data"],
		"pages"		=>	$pages
	]);
});

// Exibe detalhes do produto
$app->get("/products/:desurl", function($desurl) {
	$product = new Product();
	$product->getFromURL($desurl);

	$page = new Page();
	$page->setTpl("product-detail", [
		"product"		=>	$product->getValues(),
		"categories"	=>	$product->getCategories()
	]);
});

// Carrinho de compras
$app->get("/cart", function() {
	$cart = Cart::getFromSession();

	$page = new Page();
	$page->setTpl("cart", [
		'cart'		=>	$cart->getValues(),
		'products'	=>	$cart->getProducts(),
		'error'		=>	Cart::getMsgError()
	]);
});

// Adiciona um produto ao carrinho
$app->get("/cart/:idproduct/add", function($idproduct) {
	$product = new Product;
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
	for ($i = 0; $i < $qtd; $i ++) $cart->addProduct($product);

	header("Location: /cart");
	exit();
});

// Remove um produto do carrinho
$app->get("/cart/:idproduct/minus", function($idproduct) {
	$product = new Product;
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	$cart->removeProduct($product);

	header("Location: /cart");
	exit();
});

// Remove todos os produtos com idproduct do carrinho
$app->get("/cart/:idproduct/remove", function($idproduct) {
	$product = new Product;
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit();
});

// Calcular frete dos produtos do carrinho
$app->post("/cart/freight", function() {
	$cart = Cart::getFromSession();
	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit();
});

// Finalizar compra
$app->get("/checkout", function() {
	User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$addr = new Address();

	$page = new Page();
	$page->setTpl("checkout", [
		'cart'		=>	$cart->getValues(),
		'address'	=>	$addr->getValues()
	]);
});

// Login de usuário cliente
$app->get("/login", function() {
	$page = new Page();
	$page->setTpl("login", [
		'error'			=>	User::getError(),
		'errorRegister'	=>	User::getErrorRegister(),
		'registerValues'=>	$_SESSION['registerValues'] ?? ['name' => '', 'email' => '', 'phone' => '']
	]);
});

// Verifica login de usuário cliente
$app->post("/login", function() {
	try {
		User::login($_POST['login'], $_POST['password']);
	} catch (Exception $e) {
		User::setError($e->getMessage());
	}	

	header("Location: /checkout");
	exit();
});

// Logout de usuário cliente
$app->get("/logout", function() {
	User::logout();

	header("Location: /login");
	exit();
});

// Cadastro de usuário cliente
$app->post("/register", function() {
	$_SESSION['registerValues'] = $_POST;

	if (!isset($_POST['name']) || $_POST['name'] == '') {
		User::setErrorRegister("Preencha o seu nome.");

		header("Location: /login");
		exit();
	}

	if (!isset($_POST['email']) || $_POST['email'] == '') {
		User::setErrorRegister("Preencha o seu e-mail.");

		header("Location: /login");
		exit();
	}

	if (!isset($_POST['password']) || $_POST['password'] == '') {
		User::setErrorRegister("Preencha a sua senha.");

		header("Location: /login");
		exit();
	}

	if (User::checkLoginExist($_POST['email'])) {
		User::setErrorRegister("Este e-mail já existe em nosso cadastro.");

		header("Location: /login");
		exit();
	}

	$user = new User();
	$user->setData([
		'inadmin'		=>	0,
		'deslogin'		=>	$_POST['email'],
		'desperson'		=>	$_POST['name'],
		'desemail'		=>	$_POST['email'],
		'despassword'	=>	$_POST['password'],
		'nrphone'		=>	$_POST['phone']
	]);
	$user->save();
	User::login($_POST['email'], $_POST['password']);

	header("Location: /checkout");
	exit();
});

// Esqueci a senha (entrada de dados)
$app->get("/forgot", function() {
	$page = new Page();
	$page->setTpl("forgot");
});

// Esqueci a senha (tratamento)
$app->post("/forgot", function() {
	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit();
});

// Esqueci a senha (e-mail enviado)
$app->get("/forgot/sent", function() {
	$page = new Page();
	$page->setTpl("forgot-sent");
});

// Esqueci a senha (redefinindo senha)
$app->get("/forgot/reset", function() {
	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();
	$page->setTpl("forgot-reset", array(
		"name"	=>	$user["desperson"],
		"code"	=>	$_GET["code"]
	));
});

// Esqueci a senha (salvando nova senha)
$app->post("/forgot/reset", function() {
	$forgot = User::validForgotDecrypt($_POST["code"]);
	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();
	$user->get((int)$forgot["iduser"]);
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, ["cost" => 12]);
	$user->setPassword($password);

	$page = new Page();
	$page->setTpl("forgot-reset-success");
});