<?php
class UserGroup_controller {
	function init() {
		Auth::loadClass("Ou_api");		
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

		$data['Parent'] = $parent;		
		return $data;
	}
}