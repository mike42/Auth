<?php

namespace Auth\service;

use Auth\Auth;
use Auth\misc\Database;
use Auth\model\Account_model;
use Auth\model\AccountOwner_model;
use Auth\model\ListDomain_model;
use Auth\model\Ou_model;
use Auth\model\Service_model;
use Auth\model\UserGroup_model;
use Auth\service\account_service;

/**
 * This is the API to interact with all account services.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
abstract class account_service {
	protected $service;
	
	abstract public function __construct(Service_model $service);
	
	/**
	 * Make a new user account on this service.
	 * 
	 * @param Account_model $a The account to create
	 */
	abstract public function accountCreate(Account_model $a);
	/**
	 * Delete a user account from this service.
	 *  
	 * @param string $account_login The login of the deleted account
	 * @param ListDomain_model $d The domain name of the deleted account
	 * @param Ou_model $o Unit to search for the login in.
	 */
	abstract public function accountDelete($account_login, ListDomain_model $d, Ou_model $o);
	/**
	 * Update the login and display name for an account.
	 * 
	 * @param Account_model $a The account to update
	 * @param string $account_old_login The login to search for (may be different to the one stored currently, if it has been changed)
	 */
	abstract public function accountUpdate(Account_model $a, $account_old_login);
	/**
	 * Disable a user account.
	 * 
	 * @param Account_model $a The user account to disable
	 */
	abstract public function accountDisable(Account_model $a);
	/**
	 * Enable a user account.
	 * 
	 * @param Account_model $a The user account to enable
	 */
	abstract public function accountEnable(Account_model $a);
	/**
	 * Relocate a user account
	 * 
	 * @param Account_model $a	The account to re-locate
	 * @param Ou_model $o		Unit that the account was formerly located under
	 */
	abstract public function accountRelocate(Account_model $a, Ou_model $old_parent);
	/**
	 * Set the password on an account.
	 *
	 * @param Account_model $a The account to set
	 * @param string p The password to use
	 */
	abstract public function accountPassword(Account_model $a, $p);
	/**
	 * Search an organizational unit recursively, looking for changes.
	 * The local database will be updated to reflect any changes here.
	 * 
	 * @param Ou_model $o The organizational unit to search.
	 */
	abstract public function recursiveSearch(Ou_model $o);
	/**
	 * Create a new group.
	 * 
	 * @param Group_model $g The group to create.
	 */
	abstract public function groupCreate(UserGroup_model $g);
	/**
	 * Delete a group
	 * 
	 * @param string $group_cn			The 'common name' of the group that was deleted.
	 * @param ListDomain_model $d		The domain to find the group under
	 * @param Ou_model $o				The organziational unit to search for this group in.
	 */
	abstract public function groupDelete($group_cn, ListDomain_model $d, Ou_model $o);
	/**
	 * Add a user account to a group.
	 * 
	 * @param Account_model $a The user account to add
	 * @param Group_model $g The group to add it to
	 */
	abstract public function groupJoin(Account_model $a, UserGroup_model $g);
	/**
	 * Remove a user account from a group.
	 * 
	 * @param Account_model $a	The user account to remove
	 * @param Group_model $g	The group to remove it from
	 */
	abstract public function groupLeave(Account_model $a, UserGroup_model $g);
	/**
	 * Add a group to a group.
	 * 
	 * @param Group_model $parent	The parent group
	 * @param Group_model $child	The group to add
	 */
	abstract public function groupAddChild(UserGroup_model $parent, UserGroup_model $child);
	/**
	 * Remove a group from a group.
	 * 
	 * @param Group_model $parent The parent group
	 * @param Group_model $child The group to remove
	 */
	abstract public function groupDelChild(UserGroup_model $parent, UserGroup_model $child);
	/**
	 * Relocate the group to a different organizational unit
	 * 
	 * @param UserGroup_model $g	The group to re-locate
	 * @param Ou_model $old_parent	The organizational unit which it was formerly located under
	 */
	abstract public function groupMove(UserGroup_model $g, Ou_model $old_parent);
	/**
	 * Change the name of a group
	 * 
	 * @param UserGroup_model $g		The group to rename
	 * @param unknown_type $ug_old_cn	The old 'common name' of the group
	 */
	abstract public function groupRename(UserGroup_model $g, $ug_old_cn);
	/**
	 * Create a new organizational unit.
	 * 
	 * @param Ou_model $o The organizational unit to create
	 */
	abstract public function ouCreate(Ou_model $o);
	/**
	 * Delete an organizational unit
	 * 
	 * @param string $ou_name	The name of the unit to delete.
	 * @param string $d			The domain to find this unit in.
	 * @param Ou_model $o		The parent unit.
	 */
	abstract public function ouDelete($ou_name, ListDomain_model $d, Ou_model $o);
	/**
	 * Move an organizational unit
	 * @param Ou_model $o			The organizational unit to move
	 * @param Ou_model $old_parent	The unit which it was formerly located under.
	 */
	abstract public function ouMove(Ou_model $o, Ou_model $old_parent);
	/**
	 * Rename an organizational unit
	 * 
	 * @param Ou_model $o
	 * @param Ou_model $name
	 */
	abstract public function ouRename(Ou_model $o, $ou_old_name);
	
	/**
	 * Create remote groups/units which are missing, un-track deleted users, and push group membership details out
	 * 
	 * @param Ou_model $o
	 */
	abstract public function syncOu(Ou_model $o);
	
	/**
	 * Generate a junk password that nobody is supposed to know.
	 * @return string
	 */
	static protected function junkPassword() {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()-_=+";
		$p = "";
		for($i = 0; $i < 16; $i++) {
			$p .= substr($chars, rand(0, strlen($chars) - 1), 1);
		}
		return $p;
	}
	
	/**
	 * Given an accountOwner, return their account on this service, or false.
	 *
	 * @param AccountOwner_model $ao
	 */
	protected function getOwnersAccount(AccountOwner_model $ao) {
		$ao -> populate_list_Account();
		foreach($ao -> list_Account as $a) {
			if($a -> service_id == $this -> service -> service_id) {
				return $a;
			}
		}
		return false;
	}
}
?>