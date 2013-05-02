<?php
class Ou_controller {
	function init() {
		Auth::loadClass("Ou_api");
	}
	
	function view($ou_id = null) {
		$data = array('current' => 'Ou');
		if($ou_id == null) {
			$root = Ou_api::getHierarchy();
			$data['Ou'] = $root;
			return $data;
		}

		$data['error'] = '404';
		return $data;
	}
	
	function create($ou_id = null) {
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
				web::redirect(web::constructURL("Ou", "view", array($ou -> ou_id), "html"));
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}			
		}
		return $data;
	}
}