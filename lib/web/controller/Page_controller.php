<?php 

class Page_controller {
	function view($page) {
		return array('current' => 'Dashboard');
	}
	
	function logout() {
		if(isset($_SESSION)) {
			session_destroy();
		}
		Web::redirect(Web::constructURL('Page', 'view', array(''), 'html'));
		exit(0);
	}
}

?>