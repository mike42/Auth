<?php 
class Account_controller {
	function init() {
		Auth::loadClass("Account_api");
		Auth::loadClass("AccountOwner_api");
	}
	
	function view($account_id = false) {
		$data = array('current' => "Ou");
		try {
			$data['Account'] = Account_api::get($account_id);
		} catch(Exception $e) {
			$data['error'] = '404';
		}
		return $data;
	}

	function create($owner_id) {
		$data = array('current' => "Ou");
		try {
			$data['AccountOwner'] = AccountOwner_api::get($owner_id);
			$data['ListDomain'] = ListDomain_model::list_by_domain_enabled('1');
			foreach($data['ListDomain'] as $key => $domain) {
				$domain -> populate_list_ListServiceDomain();
			}
		} catch(Exception $e) {
			$data['error'] = '404';
		}
		return $data;		
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