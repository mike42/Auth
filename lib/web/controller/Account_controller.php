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
		
		try {
			if(isset($_POST['action'])) {
				$action = $_POST['action'];
				switch($action) {
					case "delete":
						$owner_id = $data['Account'] -> owner_id;
						Account_api::delete($data['Account'] -> account_id);
						web::redirect(web::constructURL("AccountOwner", "view", array($owner_id), "html"));
						break;
					case "disable":
						$data['Account'] = Account_api::disable($data['Account'] -> account_id);
						break;
					case "enable":
						$data['Account'] = Account_api::disable($data['Account'] -> account_id);
						break;
				}
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
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

			if(isset($_POST['account_login']) && isset($_POST['domain_id'])) {
				try {
					/* Get basic data */
					$domain_id = $_POST['domain_id'];
					if($domain_id == "" || !isset($_POST['services-'. $_POST['domain_id']])) {
						throw new Exception("Please select a domain for the account");
					}
					$account_login = $_POST['account_login'];
					$service_id = $_POST['services-'. $domain_id];
					if($service_id == "") {
						throw new Exception("Please select a service for the account");
					}
						
					/* Attempt to create the account */
					Account_api::create($owner_id, $account_login, $domain_id, $service_id);
					Web::redirect(Web::constructURL("AccountOwner", "view", array((int)$data['AccountOwner'] -> owner_id), "html"));
				} catch(Exception $e) {
					$data['message'] = $e -> getMessage();
				}
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
	
	function rename($account_id) {
		$data = array('current' => "Ou");
		try {
			$data['Account'] = Account_api::get($account_id);
		} catch(Exception $e) {
			$data['error'] = '404';
		}
		return $data;
	}
}
?>