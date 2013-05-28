<?php 

require_once(dirname(__FILE__) . "/ldap_service.php");

class ad_service extends ldap_service {
	private $service;
	
	function __construct(Service_model $service) {
		$this -> service = $service;
	}
	
	/**
	 * Make a new user account on this service.
	 * 
	 * @param Account_model $a The account to create
	 */
	public function accountCreate(Account_model $a) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Delete a user account from this service.
	 *  
	 * @param string $account_login The login of the deleted account
	 * @param ListDomain_model $d The domain name of the deleted account
	 * @param Ou_model $o Unit to search for the login in.
	 */
	public function accountDelete($account_login, ListDomain_model $d, Ou_model $o) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Update the login and display name for an account.
	 * 
	 * @param Account_model $a The account to update
	 * @param string $account_old_login The login to search for (may be different to the one stored currently, if it has been changed)
	 */
	public function accountUpdate(Account_model $a, $account_old_login) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Disable a user account.
	 * 
	 * @param Account_model $a The user account to disable
	 */
	public function accountDisable(Account_model $a) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Enable a user account.
	 * 
	 * @param Account_model $a The user account to enable
	 */
	public function accountEnable(Account_model $a) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Relocate a user account
	 * 
	 * @param Account_model $a	The account to re-locate
	 * @param Ou_model $o		Unit that the account was formerly located under
	 */
	public function accountRelocate(Account_model $a, Ou_model $old_parent) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Set the password on an account.
	 *
	 * @param Account_model $a The account to set
	 * @param string p The password to use
	 */
	public function accountPassword(Account_model $a, $p) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Search an organizational unit recursively, looking for changes.
	 * The local database will be updated to reflect any changes here.
	 * 
	 * @param Ou_model $o The organizational unit to search.
	 */
	public function recursiveSearch(Ou_model $o) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Create a new group.
	 * 
	 * @param Group_model $g The group to create.
	 */
	public function groupCreate(UserGroup_model $g) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Delete a group
	 * 
	 * @param string $group_cn			The 'common name' of the group that was deleted.
	 * @param ListDomain_model $d		The domain to find the group under
	 * @param Ou_model $o				The organziational unit to search for this group in.
	 */
	public function groupDelete($group_cn, ListDomain_model $d, Ou_model $o) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Add a user account to a group.
	 * 
	 * @param Account_model $a The user account to add
	 * @param Group_model $g The group to add it to
	 */
	public function groupJoin(Account_model $a, UserGroup_model $g) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Remove a user account from a group.
	 * 
	 * @param Account_model $a	The user account to remove
	 * @param Group_model $g	The group to remove it from
	 */
	public function groupLeave(Account_model $a, UserGroup_model $g) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Add a group to a group.
	 * 
	 * @param Group_model $parent	The parent group
	 * @param Group_model $child	The group to add
	 */
	public function groupAddChild(UserGroup_model $parent, UserGroup_model $child) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Remove a group from a group.
	 * 
	 * @param Group_model $parent The parent group
	 * @param Group_model $child The group to remove
	 */
	public function groupDelChild(UserGroup_model $parent, UserGroup_model $child) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Relocate the group to a different organizational unit
	 * 
	 * @param UserGroup_model $g	The group to re-locate
	 * @param Ou_model $old_parent	The organizational unit which it was formerly located under
	 */
	public function groupMove(UserGroup_model $g, Ou_model $old_parent) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Change the name of a group
	 * 
	 * @param UserGroup_model $g		The group to rename
	 * @param unknown_type $ug_old_cn	The old 'common name' of the group
	 */
	public function groupRename(UserGroup_model $g, $ug_old_cn) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Create a new organizational unit.
	 * 
	 * @param Ou_model $o The organizational unit to create
	 */
	public function ouCreate(Ou_model $o) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Delete an organizational unit
	 * 
	 * @param string $ou_name	The name of the unit to delete.
	 * @param string $d			The domain to find this unit in.
	 * @param Ou_model $o		The parent unit.
	 */
	public function ouDelete($ou_name, ListDomain_model $d, Ou_model $o) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Move an organizational unit
	 * @param Ou_model $o			The organizational unit to move
	 * @param Ou_model $old_parent	The unit which it was formerly located under.
	 */
	public function ouMove(Ou_model $o, Ou_model $old_parent) {
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Rename an organizational unit
	 * 
	 * @param Ou_model $o
	 * @param Ou_model $name
	 */
	public function ouRename(Ou_model $o, $ou_old_name){
		//TODO
		throw new Exception("Unimplemented");
	
	}
}

?>