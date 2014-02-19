<?php
/* Require user to be logged in as assistant */
require_once(dirname(__FILE__)."/../../lib/web/Web.php");
$loginConf = Auth::getConfig('login');

/* Start session and output */
session_start();
if(!isset($_SESSION['meta-auth']['account']['ldap_username']) || !in_array($_SESSION['meta-auth']['account']['ldap_username'], $loginConf['assistant'])) {
	/* Clear session and return to login form */
	session_destroy();
	header("location: /account/");
	exit(0);
}

$data = array();
$form = 'assistant';
$action = "";
if(isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
}

switch($action) {
	case 'logout':
		/* Destroy session and return to the main page */
		header('location: /account/');
		session_destroy();
		exit(0);
	default:
		//
}


showForm($form, $data);

/**
 * Show a given form with this data
 */
function showForm($form, $data) {
	include(dirname(__FILE__).'/../../lib/web/login/page.inc');
}

?>