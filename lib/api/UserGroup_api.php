<?php
namespace Auth\api;

use Auth\api\ActionQueue_api;
use Auth\api\Ou_api;
use Auth\api\UserGroup_api;
use Auth\Auth;
use Auth\model\ListDomain_model;
use Auth\model\Ou_model;
use Auth\model\SubUserGroup_model;
use Auth\model\UserGroup_model;
use \Exception;

/**
 * This class provides an interface for managing user groups in the local database.
 * It ensures that changes are pushed onto the ActionQueue.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class UserGroup_api {
	static public function init() {
		Auth::loadClass("Ou_api");
		Auth::loadClass("UserGroup_model");
		Auth::loadClass("ActionQueue_api");
	}
	
	static public function create($group_cn, $group_name, $ou_id, $domain_id) {
		/* Normalise inputs */
		$group_cn = Auth::normaliseName($group_cn);
		$group_name = trim($group_name);
		
		/* Check domain exists */
		if(!$domain = ListDomain_model::get($domain_id)) {
			throw new Exception("No such domain");
		}
		
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
		$group -> group_domain = $domain -> domain_id;

		if(!$group -> group_id = $group -> insert()) {
			throw new Exception("There was an error adding the group to the database. Please try again.");
		}
		
		/* ActionQueue */
		ActionQueue_api::submitByDomain($group -> group_domain, 'grpCreate', $group -> group_cn, $group -> group_name, $ou -> ou_name);
		
		return $group;
	}

	/**
	 * Get a group by ID
	 * 
	 * @param int $group_id
	 * @throws Exception
	 * @return unknown
	 */
	static public function get($group_id) {
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
	static public function get_by_group_cn($group_cn) {
		if(!$ug = UserGroup_model::get_by_group_cn($group_cn)) {
			throw new Exception("No such user group");
		}
	
		$ug -> populate_list_OwnerUserGroup();
		return $ug;
	}
	
	/**
	 * Delete a group
	 * 
	 * @param integer $group_id The ID of the group to delete
	 * @return boolean
	 */
	static public function delete($group_id) {
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

		/* ActionQueue */
		ActionQueue_api::submitByDomain($ug -> group_domain, 'grpDelete', $ug -> group_cn, $ug -> Ou -> ou_name);
		
		$ug -> delete();
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
		
		if(self::is_member($child, $parent)) {
			throw new Exception($child -> group_name . " is already in " . $parent -> group_name . " (or one of its sub-groups)");
		}
		
		if(self::is_member($parent, $child)) {
			throw new Exception("You can't do that: " . $parent -> group_name . " is a sub-group of " . $child -> group_name);
		}
		
		$sg = new SubUserGroup_model();
		$sg -> group_id = $child_group_id;
		$sg -> parent_group_id = $parent_group_id;
		
		/* ActionQueue */
		ActionQueue_api::submitByDomain($parent -> group_domain, 'grpAddChild', $parent -> group_cn, $child -> group_cn);
		
		$sg -> insert();
		return true;
	}
	
	/**
	 * Remove a member group from a parent group
	 * 
	 * @param int $parent_group_id The ID of the parent group
	 * @param int $child_group_id The ID of the child group
	 * @throws Exception
	 * @return boolean
	 */
	static public function delchild($parent_group_id, $child_group_id) {
		$parent = self::get($parent_group_id);
		$child = self::get($child_group_id);
		
		if(!$sg = SubUserGroup_model::get($parent_group_id, $child_group_id)) {
			throw new Exception("Groups are not related");
		}
				
		/* ActionQueue */
		ActionQueue_api::submitByDomain($parent -> group_domain, 'grpDelChild', $parent -> group_cn, $child -> group_cn);
		
		$sg -> delete();
		return true;
	}
	
	/**
	 * Relocates a group under a different parent OU.
	 * 
	 * @param unknown_type $group_id
	 * @param unknown_type $ou_id
	 * @return void|boolean
	 */
	function move($group_id, $ou_id) {
		$ug = self::get($group_id);
		if($ug -> ou_id == $ou_id) {
			/* No need to move (just pretend and silently do nothing) */
			return;
		}

		$old_ou = $ug -> Ou -> ou_name;
		$ou = Ou_model::get($ou_id);
		$ug -> ou_id = $ou -> ou_id;
		
		/* ActionQueue */
		ActionQueue_api::submitByDomain($ug -> group_domain, 'grpMove', $ug -> group_cn, $old_ou);
		
		$ug -> update();
		
		return true;
	}
	
	/**
	 * Get a list of child groups
	 * 
	 * @param integer $group_id The ID of the group to check
	 * @return multitype:NULL Array of SubUserGroup_model, containing child groups
	 */
	public static function list_children($group_id) {
		$child_sg = SubUserGroup_model::list_by_parent_group_id($group_id);
		$ret = array();
		foreach($child_sg as $sg) {
			$ret[] = $sg -> UserGroup;
		}
		return $ret;
	}
	
	/**
	 * Get a list of parent groups
	 *
	 * @param integer $group_id The ID of the group to check
	 * @return multitype:NULL Array of SubUserGroup_model, containing parent groups
	 */
	public static function list_parents($group_id) {
		$parent_sg = SubUserGroup_model::list_by_group_id($group_id);
		$ret = array();
		foreach($parent_sg as $sg) {
			/* Fairly inefficient workaround for lack of smarts in DB model generation */
			$ret[] = UserGroup_model::get($sg -> parent_group_id);
		}
		return $ret;
	}

	/**
	 * Give a group a new name
	 * 
	 * @param int $group_id
	 * @param string $group_name
	 * @param string $group_cn
	 * @throws Exception
	 * @return UserGroup_model the group after the change is applied
	 */
	public static function rename($group_id, $group_name, $group_cn) {
		/* Normalise inputs and load existing group */
		$group_cn = Auth::normaliseName($group_cn);
		$group_name = trim($group_name);
		$group = self::get($group_id);
		
		if($group -> group_name == $group_name && $group -> group_cn == $group_cn) {
			/* No need to do anything */
			return $group;
		}
		
		/* The start of this is very similar to create() above */
		if($group_cn == "") {
			throw new Exception("Please enter a valid short name for the group.");
		}
		if($group_name == "") {
			throw new Exception("Please enter a valid long name for the group.");
		}

		/* Check against existing groups */
		if($group -> group_cn != $group_cn && $ug = UserGroup_model::get_by_group_cn($group_cn)) {
			throw new Exception("A user group with that name already exists.");
		}
		
		/* Also check for OU clashes */
		if($ou = Ou_model::get_by_ou_name($group_cn)) {
			throw new Exception("An organizational unit exists with that name.");
		}
		
		/* Update in local database */
		$oldcn = $group -> group_cn;
		$group -> group_cn = $group_cn;
		$group -> group_name = $group_name;
		
		/* ActionQueue */
		ActionQueue_api::submitByDomain($group -> group_domain, 'grpRename', $group -> group_cn, $oldcn);

		$group -> update();

		return $group;
	}
	
	/**
	 * Check whether $parent is a sub-group of $child (used to avoid creating membership cycles).
	 * Note tha this will return false if $parent == $child, so you should check that yourself.
	 * 
	 * @param UserGroup_model $child
	 * @param UserGroup_model  $parent
	 * @param array $visited
	 * @throws Exception
	 * @return boolean
	 */
	private static function is_member(UserGroup_model $child, UserGroup_model $parent, $visited = array()) {
		$children = self::list_children($parent -> group_id);
		foreach($children as $ug) {
			if($ug -> group_id == $child -> group_id) {
				/* Found sub-group we were looking for */
				return true;
			}
			if(isset($visited[$ug -> group_id])) {
				/* Prevent infinite loops */
				throw new Exception("Uh Oh! Membership cycle found. Try deleting lots of groups-in-groups to fix this.");
			}
			$visited[$ug -> group_id] = true;
			if(self::is_member($child, $ug, $visited)) {
				/* Found $child as a sub-group of this sub-group */
				return true;
			}
		}
		
		return false;
	}
}

?>