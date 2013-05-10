<?php 
class Account_controller {
	
	
	function view($account_id = false) {
		// TODO: If no account is given, go to Ou_controller::view();
	}

	function create() {
		return array('current' => 'Ou', 'error' => '404');
	}
}
?>