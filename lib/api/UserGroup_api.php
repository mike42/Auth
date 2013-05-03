<?php
 
/**
 * This class provides an interface for managing user groups in the local database.
 * It ensures that changes are pushed onto the ActionQueue.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class UserGroup_api {
	function init() {
		Auth::loadClass("Ou_api");
		Auth::loadClass("UserGroup_model");
	}
	
	function create($group_cn, $group_name, $ou_id) {
		/* Normalise inputs */
		$group_cn = Auth::normaliseName($group_cn);
		$group_name = trim($group_name);
		if($group_cn == "") {
			throw new Exception("Please enter a valid short name for the group.");
		}
		if($group_name == "") {
			throw new Exception("Please enter a valid long name for the group.");
		}
		
		/* Check against existing groups */
		if($group = UserGroup_model::get_by_group_cn($group_cn)) {
			throw new Exception("A user group with that name already exists.");
		}
		
		/* Also check for OU clashes */
		if($ou = Ou_model::get_by_ou_name($group_cn)) {
			throw new Exception("An organizational unit exists with that name.");
		}

		/* Make sure the parent exists */
		$ou = Ou_api::get($ou_id);
		
		/* Insert actual group */
		$group = new UserGroup_model();
		$group -> group_name = $group_name;
		$group -> group_cn = $group_cn;
		$group -> ou_id = $ou -> ou_id;
		if(!$group -> group_id = $group -> insert()) {
			throw new Exception("There was an error adding the group to the database. Please try again.");
		}
		
		return $group;
	}
	
	function get($group_id) {
		if(!$ug = UserGroup_model::get((int)$group_id)) {
			throw new Exception("No such user group");
		}

		$ug -> populate_list_OwnerUserGroup();
		$ug -> populate_list_SubUserGroup();

		return $ug;
	}
	
	function delete($group_id) {
		throw new Exception("unimplemented");
	}
}

?>