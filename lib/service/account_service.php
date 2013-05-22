<?php 
/**
 * This abstract class provides an interface for interacting with most account-providing services.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
abstract class account_service {
	/**
	 * Make a new user account on this service.
	 * 
	 * @param Account_model $a The account to create
	 */
	abstract protected function accountCreate(Account_model $a);
	
	/**
	 * Delete a user account from this service.
	 * 
	 * @param string $account_login The login name of the account
	 * @param string $account_domain The domain which the account is on (services may serve multiple domains)
	 */
	abstract protected function accountDelete($account_login, ListDomain_model $account_domain);
	
	/**
	 * Update the login and display name for an account.
	 * 
	 * @param Account_model $a The new account details
	 * @param string $account_old_login The username to search for -- If an account has been renamed, then this will be different to the username stored in the above object.
	 * @param string $owner_firstname
	 * @param string $owner_surname
	 */
	abstract protected function accountUpdate(Account_model $a, $account_old_login, $owner_firstname, $owner_surname);

	/**
	 * Disable a user account.
	 * 
	 * @param Account_model $a The user account to disable
	 */
	abstract protected function accountDisable(Account_model $a);
	
	/**
	 * Enable a user account.
	 * 
	 * @param Account_model $a The user account to enable
	 */
	abstract protected function accountEnable(Account_model $a);
	
	/**
	 * Re-base an account under a different organizational unit.
	 * 
	 * @param Account_model $a
	 */
	abstract protected function accountRelocate(Account_model $a, Ou_model $o);

	/**
	 * Set the password on an account.
	 *
	 * @param Account_model $a The account to set
	 * @param string p The password to use
	 */
	abstract protected function accountPassword(Account_model $a, $p);
	
	/**
	 * Search an organizational unit recursively, looking for changes.
	 * 
	 * @param Ou_model $o The organizational unit to search.
	 */
	abstract protected function recursiveSearch(Ou_model $o);

	/**
	 * Create a new group.
	 * 
	 * @param Group_model $g The group to create.
	 */
	//abstract protected function groupCreate(UserGroup_model $g);
	
	/**
	 * Delete a group.
	 * 
	 * @param Group_model $g 
	 */
	//abstract protected function groupDelete(string $group_cn, string $group_domain);
	
	/**
	 * Add a user account to a group.
	 * 
	 * @param Account_model $a The user account to add
	 * @param Group_model $g The group to add it to
	 */
	//abstract protected function groupJoin(Account_model $a, UserGroup_model $g);

	/**
	 * Remove a user account from a group.
	 * 
	 * @param Account_model $a The user account to remove
	 * @param Group_model $g The group to remove it from
	 */
	//abstract protected function groupLeave(Account_model $a, UserGroup_model $g);

	/**
	 * Add a group to a group.
	 * 
	 * @param Group_model $parent The parent group
	 * @param Group_model $child The group to add
	 */
	//abstract protected function groupAddChild(UserGroup_model $parent, UserGroup_model $child);

	/**
	 * Remove a group from a group.
	 * 
	 * @param Group_model $parent The parent group
	 * @param Group_model $child The group to remove
	 */
	//abstract protected function groupDelChild(UserGroup_model $parent, UserGroup_model $child);

	/**
	 * Relocate the group to a different organizational unit
	 * 
	 * @param UserGroup_model $g
	 * @param Ou_model $o
	 */
	//abstract protected function groupMove(UserGroup_model $g, Ou_model $o);
	
	/**
	 * Change the name of a user group
	 * 
	 * @param UserGroup_model $g
	 * @param string $ug_old_cn
	 * @param string $group_name
	 */
	//abstract protected function groupRename(UserGroup_model $g, $ug_old_cn, $group_name);
	
	/**
	 * Create a new organizational unit.
	 * 
	 * @param Ou_model $o The organizational unit to create
	 */
	abstract protected function ouCreate(Ou_model $o);
	
	/**
	 * Delete an organizational unit.
	 * 
	 * @param string $ou_name The name of the unit
	 */
	abstract protected function ouDelete($ou_name, ListDomain_model $d);
	
	/**
	 * Move an organizational unit
	 * @param Ou_model $o
	 * @param Ou_model $parent
	 */
	abstract protected function ouMove(Ou_model $o, Ou_model $parent);
	
	/**
	 * @param Ou_model $o
	 * @param Ou_model $name
	 */
	abstract protected function ouRename($ou_old_name, Ou_model $o);

	/**
	 * Generate a junk password that nobody is supposed to know.
	 * @return string
	 */
	static function junkPassword() {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()-_=+";
		$p = "";
		for($i = 0; $i < 16; $i++) {
			$p .= substr($chars, rand(0, strlen($chars) - 1), 1);
		}
		return $p;
	}
}
?>