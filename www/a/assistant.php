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
	case 'search':
		if(isset($_POST['term'])) {
			$term = $_POST['term'];
		}
		Auth::loadClass("Account_model");
		echo json_encode(Account_model::search_by_service_domain($term, $loginConf['assist']['service_id'], $loginConf['assist']['domain_id']));
		exit(0);
		break;
	case 'logout':
		/* Destroy session and return to the main page */
		header('location: /account/');
		session_destroy();
		exit(0);
	default:
		Auth::loadClass("AccountOwner_api");
		Auth::loadClass("Account_model");
		if(isset($_POST['owner_id']) && isset($_POST['uname'])) {
			$owner_id = $_POST['owner_id'];
			$uname = $_POST['uname'];
			try {
				if($owner_id == "") {
					/* Lookup */
					$owner = AccountOwner_api::searchLogin($uname);
				} else {
					$owner = AccountOwner_api::get($owner_id);
				}
				
				/* Verify this is in a domain which we are allowed to administer */
				if(!$account = Account_model::get_by_service_owner_unique($loginConf['assist']['service_id'], $owner -> owner_id)) {
					echo $loginConf['assist']['service_id'];
					throw new Exception("You do not have permission to log on to that account.");
				}
				if($account -> account_domain != $loginConf['assist']['domain_id']) {
					throw new Exception("You do not have permission to log on to that account.");
				}
				
				/* Figure out which account to log in as */
				if(!$login_account = Account_model::get_by_service_owner_unique($loginConf['service_id'], $owner -> owner_id)) {
					throw new Exception("That account has no valid login for Auth, so can't log you in.");
				}
				$_SESSION['meta-auth']['account']['ldap_username'] = $login_account -> account_login;
				header('location: /account/');
				exit(0);
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
}

showForm($form, $data);

/**
 * Show a given form with this data
 */
function showForm($form, $data) {
	include(dirname(__FILE__).'/../../lib/web/login/page.inc');
}

function search($term) {
	$results = Account_model::search($term);
	return $results;
}
?>