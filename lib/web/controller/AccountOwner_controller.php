<?php
class AccountOwner_controller {
	/**
	 * 
	 */
	public static function init() {
		Auth::loadClass("AccountOwner_api");
		Auth::loadClass("Ou_api");
	}
	
	/**
	 * @param unknown_type $owner_id
	 * @return multitype:string NULL 
	 */
	public function view($owner_id) {
		$data = array('current' => 'Ou');
		try {
			$data['AccountOwner'] = AccountOwner_api::get($owner_id);
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		try {
			if(isset($_POST['action'])) {
				$action = $_POST['action'];
				if($action == "delete") {
					AccountOwner_api::delete($data['AccountOwner'] -> owner_id);
					Web::redirect(Web::constructURL("Ou", "view", array($data['AccountOwner'] -> ou_id), "html"));
				} else if($action == "delGroup" && isset($_POST['group_id'])) {
					$group_id = (int)$_POST['group_id'];
					AccountOwner_api::rmfromgroup($owner_id, $group_id);
					Web::redirect(Web::constructURL("AccountOwner", "view", array($data['AccountOwner'] -> owner_id), "html"));
				}
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		
		return $data;
	}
	
	/**
	 * @param unknown_type $ou_id
	 * @return multitype:string NULL 
	 */
	public static function create($ou_id) {
		$data = array('current' => 'Ou');
		try {
			$data['Ou'] = Ou_api::get($ou_id);
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		try {
			/* Get some more data */
			$data['ListDomain'] = ListDomain_model::list_by_domain_enabled('1');
			foreach($data['ListDomain'] as $key => $domain) {
				$domain -> populate_list_ListServiceDomain();
			}

			if(isset($_POST['owner_firstname']) && isset($_POST['owner_surname']) && isset($_POST['account_login'])) {
				/* Data given to create a new account */
				$owner_firstname = $_POST['owner_firstname'];
				$owner_surname = $_POST['owner_surname'];
				$account_login = $_POST['account_login'];
				$domain_id = $_POST['domain_id'];
				$services = array();
				
				/* Check what accounts will be made */
				$sds = ListServiceDomain_model::list_by_domain_id($domain_id);
				foreach($sds as $sd) {
					if(isset($_POST['service-' . $sd -> service_id])) {
						$services[] = $sd -> service_id;
						echo $sd -> service_id . "\n";
					}
				}
				
				$ao = AccountOwner_api::create($ou_id, $owner_firstname, $owner_surname, $account_login, $domain_id, $services);
				web::redirect(web::constructURL("AccountOwner", "view", array((int)$ao -> owner_id), "html"));
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		return $data;
	}
	
	public static function addgroup($owner_id) {
		$data = array('current' => 'Ou');
		try {
			$data['AccountOwner'] = AccountOwner_api::get($owner_id);
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		if(isset($_POST['group_cn']) || isset($_POST['gname'])) {
			/* Add parent if information checks out */
			$group_cn = trim($_POST['group_cn']);
			$gname = trim($_POST['gname']);
			if($group_cn == "") {
				$group_cn = $gname;
			}

			try {
				$group = UserGroup_api::get_by_group_cn($group_cn);
				AccountOwner_api::addtogroup($data['AccountOwner'] -> owner_id, $group -> group_id);
				web::redirect(web::constructURL("AccountOwner", "view", array((int)$data['AccountOwner'] -> owner_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		
		return $data;
	}
	
	public static function pwreset($owner_id) {
		$data = array('current' => 'Ou');
		try {
			$data['AccountOwner'] = AccountOwner_api::get($owner_id);
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		if(isset($_POST['source']) && isset($_POST['password1']) && isset($_POST['password2'])) {
			try {
				if($_POST['source'] == "auto") {
					Auth::loadClass("PasswordGen");
					$password = PasswordGen::generate();
					$data['message'] = "Password changed to \"".$password . "\"";
				} else {
					if($_POST['password1'] != $_POST['password2']) {
						throw new Exception("Passwords did not match!");
					}
					$password = $_POST['password1'];
					$data['message'] = "Password set";
				}
				AccountOwner_api::pwreset($owner_id, $password, true);
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		
		return $data;
	}
	
	public static function rename($owner_id) {
		$data = array('current' => 'Ou');
		try {
			$data['AccountOwner'] = AccountOwner_api::get($owner_id);
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		if(isset($_POST['owner_firstname']) && isset($_POST['owner_surname'])) {
			$owner_firstname = $_POST['owner_firstname'];
			$owner_surname = $_POST['owner_surname'];
			try {
				AccountOwner_api::rename($data['AccountOwner'] -> owner_id, $owner_firstname, $owner_surname);
				Web::redirect(Web::constructURL("AccountOwner", "view", array((int)$data['AccountOwner'] -> owner_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		
		return $data;
	}
	
	public static function move($owner_id) {
		$data = array('current' => 'Ou');
		
		/* Load hierarchy */
		$root = Ou_api::getHierarchy();
		$data['Ou_root'] = $root;
		
		try {
			$data['AccountOwner'] = AccountOwner_api::get($owner_id);
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}

		if(isset($_POST['ou_id'])) {
			$ou_id = $_POST['ou_id'];
			try {
				AccountOwner_api::move($owner_id, $ou_id);
				Web::redirect(Web::constructURL("AccountOwner", "view", array((int)$data['AccountOwner'] -> owner_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		return $data;
	}
}