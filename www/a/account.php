<?php
require_once(dirname(__FILE__) . "/../../vendor/autoload.php");

use Auth\Auth;
use Auth\api\AccountOwner_api;
use Auth\api\ActionQueue_api;
use Auth\web\Web;

Auth::loadClass('AccountOwner_api');
$conf = Auth::getConfig('login');

/* Start session and output */
session_start();
if(isset($_SESSION['meta-auth']['account']['ldap_username']) && in_array($_SESSION['meta-auth']['account']['ldap_username'], $conf['admin'])) {
	/* Redirect to admin if logged in */
	header("location: /admin/");
	exit(0);
} else if(isset($_SESSION['meta-auth']['account']['ldap_username']) && in_array($_SESSION['meta-auth']['account']['ldap_username'], $conf['assistant'])) {
	/* Redirect to assistant if logged in */
	header("location: /assistant/");
	exit(0);
}

$data = array('active' => 'info');

if(isset($_SESSION['meta-auth']['account']['ldap_username'])) {
	/* User is logged in. Need to check some things */
	$service_id = $conf['service_id'];
	if(!$service = Service_model::get($service_id)) {
		showForm('error', array('message' => 'The login service was not found! Check that service_id in config.php is a real service.'));
		session_destroy();
		exit();
	}
	
	/* Check that account exists */
	$account_login = $_SESSION['meta-auth']['account']['ldap_username'];
	if(!$account = Account_model::get_by_account_login($account_login, $service -> service_id, $service -> service_domain)) {
		/* Valid LDAP account but no sjcauth record of it */
		showForm('error', array('message' => 'This account is not being tracked in Auth.'));
		session_destroy();
		exit();
	}

	$data['AccountOwner'] = $account -> AccountOwner;
	$data['AccountOwner'] -> populate_list_Account();
	$data['AccountOwner'] -> populate_list_OwnerUserGroup();

	/* Figure out what the user is doing */
	$form = 'account';
	if(isset($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case "password-reset":
				if(!isset($_POST['inputPassword1']) || !isset($_POST['inputPassword2'])) {
					$data['message'] = 'Invalid data';
				} else {
					$pw1 = $_POST['inputPassword1'];
					$pw2 = $_POST['inputPassword2'];
					if($pw1 != $pw2) {
						$data['active'] = 'reset';
						$data['message'] = 'Passwords do not match';
					} else {
						try {
							AccountOwner_api::pwreset($data['AccountOwner'] -> owner_id, $pw1);
							$data['active'] = 'reset';
							$data['message'] = 'Password set!';
							$data['good'] = true;

							// Start processing
							ActionQueue_api::start();
						} catch(Exception $e) {
							$data['active'] = 'reset';
							$data['message'] = $e -> getMessage();
						}
					}
				}
				break;
			case 'logout':
				/* Destroy session and return to the main page */
				header('location: /account/');
				session_destroy();
				exit(0);
		}
	}
} else {
	$form = 'login';
	if(isset($_POST['loginUsername']) && isset($_POST['loginPassword'])) {
		$username = trim(strtolower($_POST['loginUsername']));
		$password = trim($_POST['loginPassword']);
		$login = ldap_verify_credentials($conf['url'], $conf['domain'], $username, $password);
		if(!$login['success']) {
			/* delay to prevent password guessing */
			sleep(3);
			$data['message'] = $login['message'];
		} else {
			/* User has logged in, take them to the right place */
			$_SESSION['meta-auth']['account']['ldap_username'] = $username;
			if(in_array($username, $conf['admin'])) {
				header('location: /admin/');
			} else if(in_array($username, $conf['admin'])) {
				header('location: /assistant/');
			} else {
				header('location: /account/');
			}
			exit(0);
		}
	}
}

showForm($form, $data);

/**
 * Match username and password against ldap
 **/
function ldap_verify_credentials($url, $domain, $username, $password) {
	/* Check login names */
	if(!ldap_verify_uid($username)) {
		return array('success' => false, 'message' => 'Invalid login name');
	}

	/* Password is required */
	if(trim($password) == '') {
		return array('success' => false, 'message' => 'No password given');
	}

	/* Bind ldap */
	if(!$ldap_conn = ldap_connect($url)) {
		return array('success' => false, 'message' => 'Connecting to the LDAP server failed');
	}

	/* Set to protocol v3 (seems to be required to avoid "protocol error" */
	ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);

	/* Anonymous bind and search the user */
	if(!ldap_bind($ldap_conn)) {
		return array('success' => false, 'message' => 'Anonymous bind to LDAP failed');
	}
			
	$filter="(cn=$username)";

	if(!$search_res = ldap_search($ldap_conn, $domain, $filter)) {
		return array('success' => false, 'message' => 'Searching for user failed');
	}

	$info = ldap_get_entries($ldap_conn, $search_res);

	if($info["count"] < 1) {
		return array('success' => false, 'message' => 'Incorrect username or password!');
	} else if ($info['count'] > 1) {
		return array('success' => false, 'message' => 'Multiple users found with this username. You will need to delete one.');
	}

	/* One username exists. Try to bind as it */
	$dn = $info[0]['dn'];
	if(!@ldap_bind($ldap_conn, $dn, $password)) {
		return array('success' => false, 'message' => 'Incorrect username or password!');
	} else {
		return array('success' => true, 'message' => 'Login OK');
	}

	/* Finish up */
	ldap_unbind($ldap_conn);
}

/**
 * Return false if a username looks dodgy
 */
function ldap_verify_uid($input) {
	if($input != PREG_REPLACE("/[^0-9a-zA-Z]/i", '', $input)) {
		return false;
	}
	return true;
}

/**
 * Show a given form with this data
 */
function showForm($form, $data) {
	include(dirname(__FILE__).'/../../lib/web/login/page.inc');
}

?>
