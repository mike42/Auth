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
			$data['ListDomain'] = ListDomain_model::list_by_domain_enabled('1');
			foreach($data['ListDomain'] as $key => $domain) {
				$domain -> populate_list_ListServiceDomain();
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		return $data;
	}
}