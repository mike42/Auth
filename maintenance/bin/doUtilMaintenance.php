#!/usr/bin/env php
<?php
require_once(dirname(__FILE__). "/../../lib/Auth.php");
Auth::loadClass("ActionQueue_api");

$util_list = Auth::getConfig('Util');

foreach($util_list as $util_classname => $util_name) {
	echo "Doing tasks for $util_name ($util_classname)\n";
	try {
		$util_classname = $util_classname . "_util";
		Auth::loadClass($util_classname);
		$util_classname::doMaintenance();
		ActionQueue_api::runUntilEmpty(false);
	} catch(Exception $e) {
		echo "\t" . $e -> getMessage() . "\n";
	}
}
?>