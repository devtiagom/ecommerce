<?php

use \Hcode\Page;

// Rota principal (raiz)
$app->get('/', function() {
	$page = new Page();
	$page->setTpl("index");
});