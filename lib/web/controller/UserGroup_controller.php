<?php
class UserGroup_controller {
	public static function init() {
		Auth::loadClass("UserGroup_api");	
	}
	
	public static function view($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
			$data['UserGroup'] -> ListDomain -> populate_list_ListServiceDomain();
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		try {
			if(isset($_POST['action'])) {
				if($_POST['action'] == "delete") {
					UserGroup_api::delete($group_id);
					web::redirect(web::constructURL("Ou", "view", array($data['UserGroup'] -> ou_id), "html"));
				} elseif($_POST['action'] == "delchild" && isset($_POST['group_id']) && isset($_POST['parent_group_id'])) {
					$child_group_id = $_POST['group_id'];
					$parent_group_id = $_POST['parent_group_id'];
					if(!($child_group_id == $group_id || $parent_group_id == $group_id)) {
						/* A small safeguard */
						throw new Exception("Cannot delete from a group that isn't being viewed.");
					}
					UserGroup_api::delchild($parent_group_id, $child_group_id);
				} elseif($_POST['action'] == "delAo" && isset($_POST['owner_id']) && isset($_POST['group_id'])) {
					$owner_id = (int)$_POST['owner_id'];
					if($group_id != (int)$_POST['group_id']) {
						throw new Exception("Cannot delete user from a group that isn't being viewed.");
					}
					AccountOwner_api::rmfromgroup($owner_id, $group_id);
					web::redirect(web::constructURL("UserGroup", "view", array($group_id), "html"));
				}
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		
		$data['Children'] = UserGroup_api::list_children($group_id);
		$data['Parents'] = UserGroup_api::list_parents($group_id);
		return $data;
	}
	
	/**
	 * @param string $ou_id The ID of the organizational unit for this group to go in.
	 */
	public static function create($ou_id = null) {
		$data = array('current' => 'Ou');		
		if($ou_id == null || !$parent = Ou_model::get($ou_id)) {
			$data['error'] = '404';
			return $data;
		}

		$data['ListDomain'] = ListDomain_model::list_by_domain_enabled('1');
		if(isset($_POST['group_name']) && isset($_POST['domain_id'])) {
			$group_name = $_POST['group_name'];
			$domain_id = $_POST['domain_id'];
			$group_cn = Auth::normaliseName($group_name);
			try {
				if($group_cn == '') {
					throw new Exception("Please enter a name for the group");
				}
				if($domain_id == '') {
					throw new Exception("Please select a domain for the group");
				}
				$ug = UserGroup_api::create($group_cn, $group_name, $parent -> ou_id, $domain_id);
				Web::redirect(Web::constructURL("UserGroup", "view", array($ug -> group_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		$data['Parent'] = $parent;		
		return $data;
	}
	
	public static function rename($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		if(isset($_POST['group_name']) && isset($_POST['group_cn'])) {
			$group_name = $_POST['group_name'];
			$group_cn = $_POST['group_cn'];
			try {
				UserGroup_api::rename($group_id, $group_name, $group_cn);
				Web::redirect(Web::constructURL("UserGroup", "view", array($ug -> group_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		
		return $data;
	}
	
	public static function move($group_id) {
		$data = array('current' => 'Ou');
		$root = Ou_api::getHierarchy();
		$data['Ou_root'] = $root;
		
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		if(isset($_POST['ou_id']) && isset($_POST['group_id'])) {
			try {
				if($_POST['group_id'] != $group_id) {
					throw new Exception("Bad group number. Please try again");
				}
				$ou_id = (int)$_POST['ou_id'];
				UserGroup_api::move($group_id, $ou_id);
				Web::redirect(Web::constructURL("UserGroup", "view", array($ug -> group_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		
		return $data;
	}
	
	public static function addparent($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
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
				$parent = UserGroup_api::get_by_group_cn($group_cn);
				UserGroup_api::addchild($parent -> group_id, $group_id);
				web::redirect(web::constructURL("UserGroup", "view", array((int)$group_id), "html"));
				return;
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		
		return $data;
	}
	
	public static function addchild($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		if(isset($_POST['group_cn']) || isset($_POST['gname'])) {
			/* Add child if information checks out */
			$group_cn = trim($_POST['group_cn']);
			$gname = trim($_POST['gname']);
			if($group_cn == "") {
				$group_cn = $gname;
			}

			try {
				$child = UserGroup_api::get_by_group_cn($group_cn);
				UserGroup_api::addchild($group_id, $child -> group_id);
				web::redirect(web::constructURL("UserGroup", "view", array((int)$group_id), "html"));
				return;
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		
		return $data;
	}
	
	public static function adduser($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}

		try {
			if(isset($_POST['owner_id'])) {
				$owner_id = (int)$_POST['owner_id'];
				AccountOwner_api::addtogroup($owner_id, $group_id);
				web::redirect(web::constructURL("UserGroup", "view", array((int)$group_id), "html"));
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		
		return $data;
	}
	
	public static function search() {
		if(!isset($_POST['term'])) {
			return array('error' => '404');
		}
		$term = $_POST['term'];
		$results = UserGroup_model::search($term);
		return array("UserGroups" => $results);
	}
}