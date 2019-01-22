<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;

// ----- formatPrice() ----------------------------------- //
function formatPrice($vlprice) {
    if (!($vlprice > 0)) $vlprice = 0;
    
    return number_format($vlprice, 2, ",", ".");
}

// ----- formatDate() ------------------------------------ //
function formatDate($date) { return date('d/m/Y', strtotime($date)); }

// ----- checkLogin() ------------------------------------ //
function checkLogin($inadmin = true) { return User::checkLogin($inadmin); }

// ----- getUserName() ----------------------------------- //
function getUserName() {
    $user = User::getFromSession();
    
    return $user->getdesperson();
}

// ----- getCartNrQtd() ---------------------------------- //
function getCartNrQtd() {
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();

    return $totals['nrqtd'];
}

// ----- getCartVlSubTotal() ----------------------------- //
function getCartVlSubTotal() {
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();

    return formatPrice($totals['vlprice']);
}