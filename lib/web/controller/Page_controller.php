<?php 

class Page_controller {
	public static function init() {
		Auth::loadClass("AccountOwner_api");
	}
	
	public static function view($page) {
		$data = array('current' => 'Dashboard');
		if(isset($_POST['owner_id']) && isset($_POST['uname'])) {
			/* Selected a user */
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
		} else if(isset($_POST['group_cn']) && isset($_POST['gname'])) {
			/* Selected a group */
			$group_cn = $_POST['group_cn'];
			$gname = $_POST['gname'];
			try {
				if($group_cn == "") {
					$group = UserGroup_api::get_by_group_cn($gname);
				} else {
					$group = UserGroup_api::get_by_group_cn($group_cn);
				}
				Web::redirect(Web::constructURL("UserGroup", "view", array($group -> group_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}

		if(isset($_POST['selected'])) {
			$data['selected'] == $_POST['selected'];
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
