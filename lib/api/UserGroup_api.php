<?php
 
/**
 * This class provides an interface for managing user groups in the local database.
 * It ensures that changes are pushed onto the ActionQueue.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class UserGroup_api {
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
		
		/* Make sure the parent exists */
		$ou = Ou_api::get($ou_id)
		
		/* Check against existing groups */
		if($group = UserGroup_model::get_by_group_cn($group_cn)) {
			throw new Exception("A user group with that name already exists.");
		}
		
		/* Also check for OU clashes */
		if($ou = Ou_model::get_by_ou_name($group_cn)) {
			throw new Exception("An organizational unit exists with that name.");
		}

		/* Insert actual group */
		$group = new UserGroup_model();
		$group -> group_name = $group_name;
		$group -> group_cn = $group_cn;
		if(!$group -> group_id = $group -> insert()) {
			throw new Exception("There was an error adding the group to the database. Please try again.");
		}
		
		return $group;
	}
}

?>