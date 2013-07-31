<?php 

class Page_controller {
	public static function init() {
		Auth::loadClass("AccountOwner_api");
	}
	
	public static function view($page) {
		$data = array('current' => 'Dashboard');
		if(isset($_POST['owner_id']) && isset($_POST['uname'])) {
			$owner_id = $_POST['owner_id'];
			$uname = $_POST['uname'];
			try {
				if($owner_id == "") {
					/* Lookup */
					$owner = AccountOwner_api::searchLogin($uname);
				} else {
					$owner = AccountOwner_api::get($owner_id);
				}
				Web::redirect(Web::constructURL("AccountOwner", "view", array($owner -> owner_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		return $data;
	}
	
	public static function logout() {
		if(isset($_SESSION)) {
			session_destroy();
		}
		Web::redirect(Web::constructURL('Page', 'view', array(''), 'html'));
		exit(0);
	}
}

?>