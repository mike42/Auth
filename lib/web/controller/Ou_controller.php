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
		$data = array('current' => 'Ou');
		if($ou_id == null) {
			$data['error'] = '404';
			return $data;
		}
			
		if(!$parent = Ou_model::get($ou_id)) {
			return $data;
		}

		$data['Parent'] = $parent;
		return $data;
	}
}