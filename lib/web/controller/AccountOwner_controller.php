<?php
class AccountOwner_controller {
	public static function init() {
		Auth::loadClass("AccountOwner_api");
		Auth::loadClass("Ou_api");
	}
	
	public static function create($ou_id) {
		$data = array('current' => 'Ou');
		try {
			$data['Ou'] = Ou_api::get($ou_id);
		} catch(Exception $e) {
			$data['error'] = '404';
		}
		
		try {			
			$data['ListDomain'] = ListDomain_model::list_by_domain_enabled('1');
			foreach($data['ListDomain'] as $key => $domain) {
				$domain -> populate_list_ListServiceDomain();
			}
		
			if(isset($_POST['owner_firstname']) && isset($_POST['owner_surname']) && isset($_POST['account_login'])) {
				throw new Exception("Account creation unimplemented");
				//$ao = AccountOwner_api::create($ou_id, $owner_firstname, $owner_surname, $account_login, $domain_id, $services);
				//core::redirect(core::constructURL("AccountOwner", "view", array((int)$ao -> owner_id), "html");
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		return $data;
	}
}