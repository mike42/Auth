#!/usr/bin/env php
<?php 
require_once(dirname(__FILE__) . "/../lib/Auth.php");
Auth::loadClass("PasswordGen");

/* Generate the given number of passwords, used for batch work */

if(count($argv) < 2) {
	die("Usage: " . $argv[0] . " number\n");
}

$count = (int)$argv[1];

for($i = 0; $i < $count; $i++) {
	echo PasswordGen::generate() . "\n";
}

?>
