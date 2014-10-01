#!/usr/bin/env php
<?php 
require_once(dirname(__FILE__) . "/../lib/Auth.php");
Auth::loadClass("PasswordGen");
Auth::loadClass("Account_model");
Auth::loadClass("AccountOwner_api");

$account_login = "guest";
$service_id = "ldap";
$account_domain= "staff";
$owner = Account_model::get_by_account_login($account_login, $service_id, $account_domain);
$password = PasswordGen::generate();
AccountOwner_api::pwreset($owner -> owner_id, $password, true);

?>