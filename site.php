<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

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

// Finalizar compra (mostra resumo do pedido antes de finalizar compra)
$app->get("/checkout", function() {
	User::verifyLogin(false);

	$addr = new Address();
	$cart = Cart::getFromSession();

	if (!isset($_GET['zipcode'])) $_GET['zipcode'] = $cart->getdeszipcode();
	
	$addr->loadFromCEP($_GET['zipcode']);
	$cart->setdeszipcode($_GET['zipcode']);
	$cart->save();
	$cart->getCalculateTotal();

	if (!$addr->getdesaddress()) $addr->setdesaddress('');
	if (!$addr->getdesnumber()) $addr->setdesnumber('');
	if (!$addr->getdescomplement()) $addr->setdescomplement('');
	if (!$addr->getdesdistrict()) $addr->setdesdistrict('');
	if (!$addr->getdescity()) $addr->setdescity('');
	if (!$addr->getdesstate()) $addr->setdesstate('');
	if (!$addr->getdescountry()) $addr->setdescountry('');
	if (!$addr->getdeszipcode()) $addr->setdeszipcode('');
	
	$page = new Page();
	$page->setTpl("checkout", [
		'cart'		=>	$cart->getValues(),
		'address'	=>	$addr->getValues(),
		'products'	=>	$cart->getProducts(),
		'error'		=>	Address::getMsgError()
	]);
});

// Finalizar compra
$app->post("/checkout", function() {
	User::verifyLogin(false);

	foreach ($_POST as $key => $value) {
		if ($key != 'descomplement' && $value === '') {
			$msg = 'Informe ' . Address::getFieldName($key) . '!';
			Address::setMsgError($msg);
			header("Location: /checkout");
			exit();
		}
	}

	$user = User::getFromSession();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$addr = new Address();
	$addr->setData($_POST);
	$addr->save();

	$cart = Cart::getFromSession();
	$cart->getCalculateTotal();

	$order = new Order();
	$order->setData([
		'idcart'	=>	$cart->getidcart(),
		'idaddress'	=>	$addr->getidaddress(),
		'iduser'	=>	$user->getiduser(),
		'idstatus'	=>	OrderStatus::OPENED,
		'vltotal'	=>	$cart->getvltotal()
	]);
	$order->save();

	// Via boleto
	//header("Location: /order/" . $order->getidorder());

	// Forma de pagamento
	switch ((int)$_POST['payment-method']) {
		// Pagamento via PagSeguro
		case 1:
			header('Location: /order/' . $order->getidorder() . '/pagseguro');
		break;

		// Pagamento via PayPal
		case 2:
			header('Location: /order/' . $order->getidorder() . '/paypal');
		break;
	}

	exit();
});

// Pagamento via PagSeguro
$app->get("/order/:idorder/pagseguro", function($idorder) {
	User::verifyLogin(false);

	$order = new Order();
	$order->get((int)$idorder);

	//$cart = $order->getCart();

	$page = new Page([
		'header'	=>	false,
		'footer'	=>	false
	]);
	$page->setTpl("payment-pagseguro", [
		'order'		=>	$order->getValues(),
		'cart'		=>	$order->getCart()->getValues(),
		'products'	=>	$order->getCart()->getProducts(),
		'phone'		=>	[
			'areaCode'	=>	substr($order->getnrphone(), 0, 2),
			'number'	=>	substr($order->getnrphone(), 2, strlen($order->getnrphone() - 1))
		]
	]);
});

// Pagamento via PayPal
$app->get("/order/:idorder/paypal", function($idorder) {
	User::verifyLogin(false);

	$order = new Order();
	$order->get((int)$idorder);

	$page = new Page([
		'header'	=>	false,
		'footer'	=>	false
	]);
	$page->setTpl("payment-paypal", [
		'order'		=>	$order->getValues(),
		'cart'		=>	$order->getCart()->getValues(),
		'products'	=>	$order->getCart()->getProducts()
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

// Profile - dados da conta do usuário
$app->get("/profile", function() {
	User::verifyLogin(false);
	$user = User::getFromSession();

	$page = new Page();
	$page->setTpl("profile", [
		'user'			=>	$user->getValues(),
		'profileMsg'	=>	User::getSuccess(),
		'profileError'	=>	User::getError()
	]);
});

// Profile - grava atualização de dados do usuário
$app->post("/profile", function() {
	User::verifyLogin(false);
	$user = User::getFromSession();

	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		User::setError('Preencha o seu nome.');
		header("Location: /profile");
		exit();
	}

	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setError('Preencha o seu e-mail.');
		header("Location: /profile");
		exit();
	}

	if ($_POST['desemail'] != $user->getdesemail()) {
		if (User::checkLoginExist($_POST['desemail'])) {
			User::setError("Este endereço de e-mail já foi cadastrado!");
			header("Location: /profile");
			exit();
		}
	}

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);
	$user->update();

	$_SESSION[User::SESSION] = $user->getValues();

	User::setSuccess("Dados alterados com sucesso!");

	header("Location: /profile");
	exit();
});

// Ordem de compra
$app->get("/order/:idorder", function($idorder) {
	User::verifyLogin(false);

	$order = new Order();
	$order->get((int)$idorder);

	$page = new Page();
	$page->setTpl("payment", [
		'order'	=>	$order->getValues()
	]);
});

// Boleto
$app->get("/boleto/:idorder", function($idorder) {
	User::verifyLogin(false);

	$order = new Order();
	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "",$valor_cobrado);
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . ', ' . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . ' - ' . $order->getdesstate() . ' - ' . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
			'res' . DIRECTORY_SEPARATOR .
			'boletophp' . DIRECTORY_SEPARATOR .
			'include' . DIRECTORY_SEPARATOR;

	require_once($path . 'funcoes_itau.php');
	require_once($path . 'layout_itau.php');
});

// Ordens de compra do usuário
$app->get("/profile/orders", function() {
	User::verifyLogin(false);
	$user = User::getFromSession();

	$page = new Page();
	$page->setTpl("profile-orders", [
		'orders'	=>	$user->getOrders()
	]);
});

// Detalhes do pedido
$app->get("/profile/orders/:idorder", function($idorder) {
	User::verifyLogin(false);

	$order = new Order();
	$order->get((int)$idorder);

	$cart = new Cart();
	$cart->get((int)$order->getidcart());
	$cart->getCalculateTotal();

	$page = new Page();
	$page->setTpl("profile-orders-detail", [
		'order'		=>	$order->getValues(),
		'cart'		=>	$cart->getValues(),
		'products'	=>	$cart->getProducts()
	]);
});

// Alterar a senha (preencher formulário)
$app->get("/profile/change-password", function() {
	User::verifyLogin(false);

	$page = new Page();
	$page->setTpl("profile-change-password", [
		'changePassError'	=>	User::getError(),
		'changePassSuccess'	=>	User::getSuccess()
	]);
});

// Alterar a senha (receber formulário)
$app->post("/profile/change-password", function() {
	User::verifyLogin(false);
	$user = User::getFromSession();

	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {
		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit();
	}

	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {
		User::setError("Digite a nova senha.");
		header("Location: /profile/change-password");
		exit();
	}

	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {
		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit();
	}

	if ($_POST['new_pass'] != $_POST['new_pass_confirm']) {
		User::setError("A nova senha e a confirmação devem ser iguais.");
		header("Location: /profile/change-password");
		exit();
	}

	if ($_POST['new_pass'] === $_POST['current_pass']) {
		User::setError("A nova senha deve ser diferente da atual.");
		header("Location: /profile/change-password");
		exit();
	}

	if (!password_verify($_POST['current_pass'], $user->getdespassword())) {
		User::setError("A senha atual está incorreta.");
		header("Location: /profile/change-password");
		exit();
	}

	$user->setdespassword(User::getPasswordHash($_POST['new_pass']));
	$user->update();
	User::setSuccess("Senha atualizada com sucesso!");

	header("Location: /profile/change-password");
	exit();
});