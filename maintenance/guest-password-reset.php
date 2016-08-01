#!/usr/bin/env php
<?php 
require_once(dirname(__FILE__). "/../../vendor/autoload.php");
use Auth\Auth;

Auth::loadClass("PasswordGen");
Auth::loadClass("Account_model");
Auth::loadClass("AccountOwner_api");

/**
 * Guest password reset.
 */
$account_login = "guest";
$service_id = "ldap";
$account_domain= "staff";

/* Set */
$owner = Account_model::get_by_account_login($account_login, $service_id, $account_domain);
$password = PasswordGen::generate();
AccountOwner_api::pwreset($owner -> owner_id, $password, false);

/* Apply */
ActionQueue_api::start();

?>
