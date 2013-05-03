<?php
class UserGroup_controller {
	function init() {
		Auth::loadClass("UserGroup_api");	
	}
	
	function view($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		if(isset($_POST['action'])) {
			if($_POST['action'] == "delete") {
				try {
					UserGroup_api::delete($group_id);
					web::redirect(web::constructURL("Ou", "view", array($data['UserGroup'] -> ou_id), "html"));
					return $data;
				} catch(Exception $e) {
					$data['error'] = "500";
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * @param string $ou_id The ID of the organizational unit for this group to go in.
	 */
	function create($ou_id = null) {
		$data = array('current' => 'Ou');
		if($ou_id == null) {
			$data['error'] = '404';
			return $data;
		}
			
		if(!$parent = Ou_model::get($ou_id)) {
			return $data;
		}

		if(isset($_POST['group_name'])) {
			$group_name = $_POST['group_name'];
			$group_cn = Auth::normaliseName($group_name);
			try {
				$ug = UserGroup_api::create($group_cn, $group_name, $parent -> ou_id);
				Web::redirect(Web::constructURL("UserGroup", "view", array($ug -> group_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		$data['Parent'] = $parent;		
		return $data;
	}
	
	function rename($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		// TODO: Check post data
		
		return $data;
	}
	
	function addparent($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		// TODO: Check post data
		
		return $data;
	}
	
	function addchild($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		// TODO: Check post data
		
		return $data;
	}
	
	function adduser($group_id) {
		$data = array('current' => 'Ou');
		try {
			$ug = UserGroup_api::get($group_id);
			$data['UserGroup'] = $ug;
		} catch(Exception $e) {
			$data['error'] = '404';
			return $data;
		}
		
		
		// TODO: Check post data
		
		return $data;
	}
}