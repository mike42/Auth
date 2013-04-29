<?php
 
/**
 * This class provides an interface for managing organizational units in the local database.
 * It ensures that changes are pushed onto the ActionQueue.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class Ou_api {
	function init() {
		Auth::loadClass("Ou_model");
	}
	
	/**
	 * Load the full hierarchy of organizational units.
	 * 
	 * @param Ou_model $parent The starting point, or null to start at the root.
	 */
	function getHierarchy(Ou_model $parent = null) {
		if($parent == null) {
			$parent = Ou_model::get_by_ou_name("root");
			// TODO: If root does not exist.
		}
		
		$parent -> populate_list_Ou();
		foreach($parent -> list_Ou as $key => $ou) {
			$parent -> list_Ou[$key] = self::getHierarchy($ou);
		}
		
		$parent -> populate_list_AccountOwner();
		$parent -> populate_list_UserGroup();
		return $parent;
	}

	/**
	 * @param string $ou_name
	 * @param string $ou_parent_id
	 * @throws Exception
	 * @return Ou_model The newly created organizational unit
	 */
	function create($ou_name, $ou_parent_id) {
		/* Check name */
		if($ou = Ou_model::get_by_name($ou_name)) {
			throw new Exception("An organizational unit with that name already exists");
		}
		
		/* Parent does not exist */
		if(!$parent = Ou_model::get($parent -> parent_id)) {
			throw new Exception("The parent organizational unit could not be found (did somebody delete it?)");
		}
		
		$ou =  new Ou_model();
		$ou -> ou_name = $ou_name;
		$ou -> ou_parent_id = $parent_id;
		if(!$ou -> insert()) {
			throw new Exception("There was an error adding the unit to the database. Please try again.");
		}

		// TODO: ActionQueue.
		return $ou;
	}
	
}

?>