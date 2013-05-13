<?php 
class Account_controller {
	function init() {
		Auth::loadClass("Account_model");
	}
	
	function view($account_id = false) {
		// TODO: If no account is given, go to Ou_controller::view();
	}

	function create() {
		return array('current' => 'Ou', 'error' => '404');
	}
	
	function search($term) {
		if(isset($_POST['term'])) {
			$term = $_POST['term'];
		}
		$results = Account_model::search($term);
		return Array("current" => "Ou", "Accounts" => $results);
	}
}
?>