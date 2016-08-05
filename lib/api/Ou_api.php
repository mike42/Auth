<?php
namespace Auth\api;

use Auth\api\AccountOwner_api;
use Auth\api\ActionQueue_api;
use Auth\api\Ou_api;
use Auth\api\UserGroup_api;
use Auth\Auth;
use Auth\model\Ou_model;
use Auth\model\UserGroup_model;
use \Exception;

/**
 * This class provides an interface for managing organizational units in the local database.
 * It ensures that changes are pushed onto the ActionQueue.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class Ou_api {
	public static function init() {
		Auth::loadClass("Ou_model");
		Auth::loadClass("UserGroup_api");
		Auth::loadClass("AccountOwner_api");
		Auth::loadClass("ActionQueue_api");
	}

	/**
	 * Load the full hierarchy of organizational units.
	 * 
	 * @param Ou_model $parent The starting point, or null to start at the root.
	 */
	public static function getHierarchy(Ou_model $parent = null) {
		if($parent == null) {
			$parent = Ou_model::get_by_ou_name("root");
			if(!$parent) {
				/* Special case for non-existent root */
				throw new Exception("Root OU does not exist");
			}
		}

		$parent -> populate_list_Ou();
		foreach($parent -> list_Ou as $key => $ou) {
			$parent -> list_Ou[$key] = self::getHierarchy($ou);
		}
		
		return $parent;
	}

	/**
	 * Create a new organizational unit
	 * 
	 * @param string $ou_name The name of the new organizational unit
	 * @param int $ou_parent_id The parent OU (to create it in)
	 * @throws Exception
	 * @return Ou_model The newly created organizational unit
	 */
	public static function create($ou_name, $ou_parent_id) {
		$ou_name = Auth::normaliseName($ou_name);
		$ou_parent_id = (int)$ou_parent_id;
		
		if($ou_name == "") {
			throw new Exception("Organization unit name cannot be empty");
		}
		
		/* Check name */
		if($ou = Ou_model::get_by_ou_name($ou_name)) {
			throw new Exception("An organizational unit with that name already exists");
		}

		if($ug = UserGroup_model::get_by_group_cn($ou_name)) {
			throw new Exception("There is a group which goes by this name. Please use a different name.");
		}
		
		/* Check parent is real */
		if(!$parent = Ou_model::get($ou_parent_id)) {
			throw new Exception("The parent organizational unit could not be found (did somebody delete it?)");
		}

		/* Attempt to insert */
		$ou =  new Ou_model();
		$ou -> ou_name = $ou_name;
		$ou -> ou_parent_id = $ou_parent_id;
		if(!$ou -> ou_id = $ou -> insert()) {
			throw new Exception("There was an error adding the unit to the database. Please try again.");
		}

		/* ActionQueue */
		ActionQueue_api::submitToEverything('ouCreate', $ou -> ou_name, $parent -> ou_name);
		return $ou;
	}
	
	/**
	 * Delete an organizational unit.
	 * 
	 * @param integer $ou_id The ID of the unit to delete.
	 * @throws Exception
	 */
	public static function delete($ou_id) {
		$ou = self::get($ou_id);
		$parent = self::get($ou -> ou_parent_id);
		
		if($ou -> ou_name == "root") {
			throw new Exception("Cannot delete the root of the organization.");
		}
		
		if(count($ou -> list_Ou) > 0) {
			if(count($ou -> list_Ou) == 1) {
				throw new Exception("This unit contains " . $ou -> list_Ou[0] -> ou_name . ", you need to delete or move that first!");
			} else {
				throw new Exception("This unit contains " . count($ou -> list_Ou) ." other units, you need to delete or move them first!");
			}
		}
		
		if(count($ou -> list_AccountOwner) > 0) {
			if(count($ou -> list_AccountOwner) == 1) {
				throw new Exception("This unit contains " . $ou -> list_AccountOwner[0] -> owner_firstname . " " . $ou -> list_AccountOwner[0] -> owner_surname . ", you need to delete or move that user first!");
			} else {
				throw new Exception("This unit is the parent of " . count($ou -> list_AccountOwner) ." user accounts, you need to delete or move them first!");
			}
		}

		if(count($ou -> list_UserGroup) > 0) {
			if(count($ou -> list_UserGroup) == 1) {
				throw new Exception("This unit contains " . $ou -> list_UserGroup[0] -> group_name . ", you need to delete or move that user first!");
			} else {
				throw new Exception("This unit contains " . count($ou -> list_UserGroup) ." user groups, you need to delete or move them first!");
			}
		}
		
		/* ActionQueue */
		ActionQueue_api::submitToEverything('ouDelete', $ou -> ou_name, $parent -> ou_name);
		
		$ou -> delete();
	}

	/**
	 * Re-base an organizational unit, to go under a different parent
	 * 
	 * @param int $ou_id The unit to move.
	 * @param int $ou_parent_id The new parent unit.
	 */
	public static function move($ou_id, $ou_parent_id) {
		$ou = self::get($ou_id);
		$oldparent = self::get($ou -> ou_parent_id);
		$parent = self::get($ou_parent_id);
		
		if(self::is_subunit($ou, $parent)) {
			throw new Exception("You can't do that: " . $parent -> ou_name . " is a sub-unit of " . $ou -> ou_name);
		}
		
		$ou -> ou_parent_id = $parent -> ou_id;
		$ou -> update();
		
		/* ActionQueue */
		ActionQueue_api::submitToEverything('ouMove', $ou -> ou_name, $oldparent -> ou_name);
	}
	
	/**
	 * Change the name of an organizational unit.
	 * 
	 * @param int $ou_id
	 * @param string $ou_name The new name of the unit (this will be filtered for sanity)
	 * @throws Exception
	 */
	public static function rename($ou_id, $ou_name) {
		$ou_name = Auth::normaliseName($ou_name);
		
		if($ou = Ou_model::get_by_ou_name($ou_name)) {
			throw new Exception("An organizational unit with that name already exists");
		}
		
		if(!$ou = Ou_model::get((int)$ou_id)) {
			throw new Exception("No such organizational unit");
		}
		
		if($ug = UserGroup_model::get_by_group_cn($ou_name)) {
			throw new Exception("There is a group which goes by this name. Please use a different name.");
		}
		
		$oldname = $ou -> ou_name;
		$ou -> ou_name = $ou_name;
		$ou -> update();
		
		/* ActionQueue */
		ActionQueue_api::submitToEverything('ouRename', $ou -> ou_name, $oldname);
	}
	
	/**
	 * Get an organizational unit by its numeric ID.
	 * 
	 * @param int $ou_id The ID of the unit go look for
	 * @throws Exception If it cannot be found
	 * @return Ou_model the Organizational unit.
	 */
	public static function get($ou_id) {
		if(!$ou = Ou_model::get((int)$ou_id)) {
			throw new Exception("No such organizational unit");
		}
		
		$ou -> populate_list_Ou();
		$ou -> populate_list_AccountOwner();
		$ou -> populate_list_UserGroup();
		
		return $ou;
	}
	
	/**
	 * Check whether $child is a sub-unit of $parent. This can be used to prevent cycles from being created.
	 * 
	 * @param Ou_model $parent
	 * @param Ou_model $child
	 * @param array $visited An array, using Ou_id values as keys, of groups already looked at.
	 * @throws Exception
	 * @return boolean
	 */
	static private function is_subunit(Ou_model $parent, Ou_model $child, $visited = array()) {
		$parent -> populate_list_Ou();
		foreach($parent -> list_Ou as $ou) {
			if(isset($visited[$ou -> ou_id])) {
				throw new Exception("Uh Oh! Sub-unit cycle found. Move the units so that they are a sub-unit of 'root' to fix this.");
			}
			$visited[$ou -> ou_id] = true;
			if($ou -> ou_id == $child -> ou_id) {
				return true;
			}
		}
		return false;
	}
}

?>