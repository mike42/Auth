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
		
		// TODO: ActionQueue.
		
		return $group;
	}
	
	/**
	 * Get a group by ID
	 * 
	 * @param int $group_id
	 * @throws Exception
	 * @return unknown
	 */
	function get($group_id) {
		if(!$ug = UserGroup_model::get((int)$group_id)) {
			throw new Exception("No such user group");
		}

		$ug -> populate_list_OwnerUserGroup();
		return $ug;
	}
	
	/**
	 * Get a group by common name (sometimes ID is not known, or we need to map a cn to an ID). Otherwise this is the smae as get(int) above.
	 * 
	 * @param string $group_cn
	 * @throws Exception
	 * @return unknown
	 */
	function get_by_group_cn($group_cn) {
		if(!$ug = UserGroup_model::get_by_group_cn($group_cn)) {
			throw new Exception("No such user group");
		}
	
		$ug -> populate_list_OwnerUserGroup();
		return $ug;
	}
	
	function delete($group_id) {
		$ug = self::get($group_id);
		
		$children = SubUserGroup_model::list_by_parent_group_id($group_id);
		foreach($children as $sg) {
			$sg -> delete();
		}
		
		$parent = SubUserGroup_model::list_by_group_id($group_id);
		foreach($parent as $sg) {
			$sg -> delete();
		}

		foreach($ug -> list_OwnerUserGroup as $og) {
			$og -> delete();
		}
		
		$ug -> delete();
		
		// TODO: ActionQueue.
		
		return true;
	}
	
	/**
	 * Add a group to another group (as a sub-group).
	 * 
	 * @param int $parent_group_id The ID of the parent group
	 * @param int $child_group_id The ID of the child group
	 * @throws Exception If something goes wrong, with a description of what happened.
	 * @return boolean True always (unless an exception is thrown)
	 */
	static public function addchild($parent_group_id, $child_group_id) {
		$parent = self::get($parent_group_id);
		$child = self::get($child_group_id);
		if($parent_group_id == $child_group_id) {
			throw new Exception("Cannot add group to itself");
		}
		
		if($sg = SubUserGroup_model::get($parent_group_id, $child_group_id)) {
			/* Already added */
			return true;
		}
		
		// TODO: Verify that there are no circular references
		
		$sg = new SubUserGroup_model();
		$sg -> group_id = $child_group_id;
		$sg -> parent_group_id = $parent_group_id;
		$sg -> insert();
		
		// TODO: ActionQueue.
		
		return true;
	}
	
	static public function delchild($parent_group_id, $child_group_id) {
		$parent = self::get($parent_group_id);
		$child = self::get($child_group_id);
		
		if(!$sg = SubUserGroup_model::get($parent_group_id, $child_group_id)) {
			throw new Exception("Groups are not related");
		}
		
		$sg -> delete();
		
		// TODO: ActionQueue.
	}
	
	function move($group_id, $ou_id) {
		$ug = self::get($group_id);
		if($ug -> ou_id == $ou_id) {
			/* No need to move (just pretend and silently do nothing) */
			return;
		}

		$ou = Ou_model::get($ou_id);
		$ug -> ou_id = $ou -> ou_id;
		$ug -> update();
		
		// TODO: ActionQueue.
		
		return true;
	}
	
	public static function list_children($group_id) {
		$child_sg = SubUserGroup_model::list_by_parent_group_id($group_id);
		$ret = array();
		foreach($child_sg as $sg) {
			$ret[] = $sg -> UserGroup;
		}
		return $ret;
	}
	
	public static function list_parents($group_id) {
		$parent_sg = SubUserGroup_model::list_by_group_id($group_id);
		$ret = array();
		foreach($parent_sg as $sg) {
			/* Fairly inefficient workaround for lack of smarts in DB model generation */
			$ret[] = UserGroup_model::get($sg -> parent_group_id);
		}
		return $ret;
	}
	
	public static function rename($group_id, $group_name, $group_cn) {
		/* Normalise inputs and load existing group */
		$group_cn = Auth::normaliseName($group_cn);
		$group_name = trim($group_name);
		$group = self::get($group_id);
		
		if($group -> group_name == $group_name && $group -> group_cn == $group_cn) {
			/* No need to do anything */
			return true;
		}
		
		/* The start of this is very similar to create() above */
		if($group_cn == "") {
			throw new Exception("Please enter a valid short name for the group.");
		}
		if($group_name == "") {
			throw new Exception("Please enter a valid long name for the group.");
		}

		/* Check against existing groups */
		if($ug = UserGroup_model::get_by_group_cn($group_cn)) {
			throw new Exception("A user group with that name already exists.");
		}
		
		/* Also check for OU clashes */
		if($ou = Ou_model::get_by_ou_name($group_cn)) {
			throw new Exception("An organizational unit exists with that name.");
		}
		
		$group -> group_cn = $group_cn;
		$group -> group_name = $group_name;
		$group -> update();
		
		// TODO: ActionQueue.
		
		return $group;
	}
}

?>