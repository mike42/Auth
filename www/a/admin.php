<?php
/* Require user to be logged in as admin */
session_start();
if(!isset($_SESSION['meta-auth']['account']['ldap_username']) || $_SESSION['meta-auth']['account']['ldap_username'] != 'admin') {
	/* Clear session and return to login form */
	session_destroy();
	header("location: /account/");
	exit(0);
}

require_once(dirname(__FILE__)."/../../lib/web/Web.php");
Auth::loadClass("ActionQueue_api");

/* Set up some basic web things */
$config['host']						= isset($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : 'localhost';
$config['webroot']					= isset($_SERVER['HTTP_HOST'])? 'http://'.$_SERVER['HTTP_HOST'].'/admin/' : '';
$config['default']['controller']	= 'Page';
$config['default']['action']		= 'view';
$config['default']['arg']			= array('home');
$config['default']['format']		= 'html';
Web::$config = $config;

/* Get page (or go to default if none is specified) */
if(isset($_GET['p']) && $_GET['p'] != '') {
	$arg = split('/', $_REQUEST['p']);
} else {
	$arg = $config['default']['arg'];
}

/* Get any extension appearing at the end of the request: */
$tail = count($arg) - 1;
$fmtsplit = explode('.', $arg[$tail]);
if(count($fmtsplit) >= 2) {
	/* One or more extensions on word, eg .rss, .tar.gz */
	$arg[$tail] = array_shift($fmtsplit);
	$fmt = implode('.', $fmtsplit);
} else {
	/* No extensions at all */
	$fmt = $config['default']['format'];
}

/* Switch for number of arguments */
if(count($arg) > 2) {
	/* $controller/$action/{foo/bar/baz}.quux */
	$controller = array_shift($arg);
	$action = array_shift($arg);

} elseif(count($arg) == 2) {
	/* No action specified - $controller/(default action)/{foo}.quux */
	$controller = array_shift($arg);
	$action = $config['default']['action'];
} elseif(count($arg) == 1) {
	/* No action or controller */
	$controller = $config['default']['controller'];
	$action = $config['default']['action'];
}

/* Figure out class and method name */
try {
	/* Execute controller code */
	$controllerClassName = $controller.'_controller';
	$controllerMethodName = $action;
	$viewClassName = $controller.'_view';
	$viewMethodName = $action . "_" . $fmt;

	Web::loadController($controllerClassName);
	Web::loadView($viewClassName);
	if(!is_callable($controllerClassName . "::" . $controllerMethodName)) {
		Web::fizzle("Controller '$controllerClassName' does not have method '$controllerMethodName'");
	}
	$ret = call_user_func_array(array($controllerClassName, $controllerMethodName), $arg);

	if(isset($ret['view'])) {
		$viewMethodName = $ret['view'] . "_" . $fmt;
	} elseif(isset($ret['error'])) {
		$viewMethodName = 'error' . "_" . $fmt;
	} elseif(isset($ret['redirect'])) {
		Web::redirect($ret['redirect']);
	}

	if(!isset($ret['aq_count'])) {
		$ret['aq_count'] = ActionQueue_api::count();
	}
	
	/* Run view code */
	if(!is_callable($viewClassName . "::" .$viewMethodName)) {
		Web::fizzle("View '$viewClassName' does not have method '$viewMethodName'");
	}
	$ret = call_user_func_array(array($viewClassName, $viewMethodName), array($ret));
} catch(Exception $e) {
	Web::fizzle("Failed to run controller: " . $e);
}
?>
