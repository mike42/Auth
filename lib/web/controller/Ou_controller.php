<?php
use Auth\Auth;
use Auth\web\Web;

class Ou_controller {
	public static function init() {
		Auth::loadClass("Ou_api");
	}

	public static function view($ou_id = null) {
		$data = array('current' => 'Ou');
		$root = Ou_api::getHierarchy();
		$data['Ou_root'] = $root;

		/* Account for unknown ID of root */
		if($ou_id == null) {
			$ou_id = $root -> ou_id;
		}

		try {
			$data['Ou'] = Ou_api::get($ou_id);
		} catch(Exception $e) {
			$data['error'] = "404";
			return $data;
		}

		if(isset($_POST['action'])) {
			if($_POST['action'] == "delete") {
				try {
					Ou_api::delete($ou_id);
					Web::redirect(Web::constructURL("Ou", "view", array($data['Ou'] -> ou_parent_id), "html"));
					return $data;
				} catch(Exception $e) {
					$data['message'] = $e -> getMessage();
				}
			}
		}
		return $data;
	}

	public static function create($ou_id = null) {
		/* Verify that correct arguments were passed */
		$data = array('current' => 'Ou');
		if($ou_id == null) {
			$data['error'] = '404';
			return $data;
		}
			
		/* Verify parent exists */
		if(!$parent = Ou_model::get($ou_id)) {
			return $data;
		}
		$data['Parent'] = $parent;

		if(isset($_POST['ou_name'])) {
			$ou_name = $_POST['ou_name'];
			try {
				$ou = ou_api::create($ou_name, $parent -> ou_id);
				Web::redirect(Web::constructURL("Ou", "view", array($ou -> ou_parent_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
		return $data;
	}

	public static function rename($ou_id = null) {
		$data = array('current' => 'Ou');
		$root = Ou_api::getHierarchy();
		$data['Ou_root'] = $root;

		if($ou_id == null || $ou_id == $root -> ou_id) {
			/* Root cannot be renamed! */
			$data['error'] = "404";
			return $data;
		}
		
		try {
			$data['Ou'] = Ou_api::get($ou_id);
		} catch(Exception $e) {
			$data['error'] = "404";
			return $data;
		}
		
		if(isset($_POST['ou_name'])) {
			try {
				Ou_api::rename($data['Ou'] -> ou_id, $_POST['ou_name']);
				Web::redirect(Web::constructURL("Ou", "view", array($data['Ou'] -> ou_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}
				
		return $data;
	}

	public static function move($ou_id = null) {
		$data = array('current' => 'Ou');
		$root = Ou_api::getHierarchy();
		$data['Ou_root'] = $root;

		if($ou_id == null || $ou_id == $root -> ou_id) {
			/* Root cannot be moved! */
			$data['error'] = "404";
			return $data;
		}

		try {
			$data['Ou'] = Ou_api::get($ou_id);
		} catch(Exception $e) {
			$data['error'] = "404";
		}
		
		if(isset($_POST['ou_id']) && isset($_POST['ou_parent_id'])) {
			$ou_id = (int)$_POST['ou_id'];
			$ou_parent_id = (int)$_POST['ou_parent_id'];
			try {
				Ou_api::move($ou_id, $ou_parent_id);
				Web::redirect(Web::constructURL("Ou", "view", array($ou_parent_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();		
			}
		}
		return $data;
	}
}