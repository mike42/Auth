#!/usr/bin/php
<?php 
require_once(dirname(__FILE__) . "/../lib/Auth.php");
Auth::loadClass("Account_api");
Auth::loadClass("Ou_api");

$a = Ou_api::getHierarchy();
print_r($a);

?>