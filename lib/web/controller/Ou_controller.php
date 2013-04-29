<?php
class Ou_controller {
	function init() {
		Auth::loadClass("Ou_api");
	}
	
	function view($ou_id = null) {
		$data = array('current' => 'Ou');
		if($ou_id == null) {
			$root = Ou_api::getHierarchy();
			$data['list_Ou'] = $root -> list_Ou;
			return $data;
		}

		$data['error'] = '404';
		return $data;
	}
}