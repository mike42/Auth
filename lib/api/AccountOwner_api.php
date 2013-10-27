<?php
/**
 * This class provides an interface for managing accounts in the local database.
 * It ensures that changes are pushed onto the ActionQueue.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class AccountOwner_api {	
	public static function init() {
		Auth::loadClass("AccountOwner_model");
		Auth::loadClass("Account_api");
		Auth::loadClass("Ou_api");
		Auth::loadClass("Account_model");
		Auth::loadClass("ListServiceDomain_model");
		Auth::loadClass("ActionQueue_api");
	}
	
	/**
	 * Create a new user account.
	 * 
	 * @param int $ou_id The organizational unit to create the user under.
	 * @param string $owner_firstname The firstname of the user.
	 * @param string $owner_surname The surname of the user.
	 * @param string $account_login The login name that this user will use.
	 * @param string $domain_id The ID of the domain to create this user's accounts on.
	 * @param array $services A list of service_ids to create accounts on. Note that the domain of these accounts is auto-selected and will not (necessarily) match $domain_id.
	 */
	public static function create($ou_id, $owner_firstname, $owner_surname, $account_login, $domain_id, $services) {
		/* Some basic validation */
		$owner_firstname = trim($owner_firstname);
		$owner_surname = trim($owner_surname);
		$account_login = Auth::normaliseName($account_login);
		if($owner_firstname == "") {
			throw new Exception("No firstname specified");
		}
		if($owner_surname == "") {
			throw new Exception("No surname specified");
		}
		if($account_login == "") {
			throw new Exception("No login name specified");
		}
		if($domain_id == "") {
			throw new Exception("No domain was selected");
		}
		if(count($services) == "") {
			throw new Exception("No services selected for this user");
		}
		
		/* Before doing anything, load up some tables and look for obvious problems */
		$ou = Ou_api::get($ou_id); // Will throw exception if not found.
		if(!$domain = ListDomain_model::get($domain_id)) {
			throw new Exception("Domain does not exist");
		}
		
		/* Now account check */
		$accounts = array();
		foreach($services as $service_id) {
			if(!$sd = ListServiceDomain_model::get($service_id, $domain_id)) {
				throw new Exception("Service '$service_id' does not exist on domain '$domain_id'.");
			}
			
			/* Look for services which are secondary on this domain */
			$d = $domain_id;
			if($sd -> sd_secondary == '1') {
				$d = $sd -> Service -> service_domain;
			}

			/* Save to list */
			$account = new Account_model();
			$account -> account_login = $account_login;
			$account -> account_enabled = 1;
			$account -> account_domain = $d;
			$account -> service_id = $service_id;
			$account -> account_enabled = 1;
			$accounts[] = $account;

			/* Look for username clashes */
			if($account = Account_model::get_by_account_login($account -> account_login, $account -> service_id, $account -> account_domain)) {
				throw new Exception("Login name '$account_login' is already in use on '$service_id' ($d).");
			}
		}
		
		/* Create AccountOwner */
		$owner = new AccountOwner_model();
		$owner -> owner_firstname = $owner_firstname;
		$owner -> owner_surname = $owner_surname;
		$owner -> ou_id = $ou_id;
		$owner -> owner_id = $owner -> insert();

		foreach($accounts as $account) {
			$account -> owner_id = $owner -> owner_id;

			ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctCreate', $account -> account_login, $ou -> ou_name, $owner -> owner_firstname,  $owner -> owner_surname);
			$account -> account_id = $account -> insert();
		}

		$owner -> populate_List_Account();
		return $owner;		
	}
	
	/**
	 * Get a user by ID, throwing an exception if the account does not exist.
	 * 
	 * @param string $owner_id
	 */
	public static function get($owner_id) {
		if(!$owner = AccountOwner_model::get($owner_id)) {
			throw new Exception("Account owner not found");
		}
		$owner -> populate_List_Account();
		$owner -> populate_List_OwnerUserGroup();
		return $owner;
	}
	
	/**
	 * Set the password for all of this user's accounts
	 * 
	 * @param int $owner_id The owner to reset password for.
	 * @param string $password The password to use.
	 * @throws Exception
	 * @return unknown
	 */
	public static function pwreset($owner_id, $password) {
		$owner = self::get($owner_id);
		$password = trim($password);

		/* Verify */
		foreach($owner -> list_Account as $account) {
			if(preg_match($account -> Service -> service_pwd_regex, $password) != 1) {
				throw new Exception("That password does not meet the requirements for " . $account -> Service -> service_name . ", so can't be used.");
			}
		}
		
		/* Submit for updating */
		foreach($owner -> list_Account as $account) {
			ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctPasswd', $account -> account_login, $password);
		}
		
		return $owner;
	}
	
	/**
	 * Move a user to a different organizational unit
	 * 
	 * @param int $owner_id
	 * @param int $ou_id
	 */
	public static function move($owner_id, $ou_id) {
		$owner = self::get($owner_id);
		$ou = Ou_api::get($ou_id);
		
		if($owner -> ou_id == $ou -> ou_id) {
			/* Nothing to do. */
			return $owner;
		}
		
		/* Update */
		$old_parent = $owner -> Ou;
		$owner -> ou_id = $ou -> ou_id;
		$owner -> update();
		
		/* ActionQueue */
		foreach($owner -> list_Account as $account) {
			ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctRelocate', $account -> account_login, $old_parent -> ou_name);
		}
		
		return $owner;
	}
	
	/**
	 * Rename a user (ie, alter their fullname / display name)
	 * 
	 * @param int $owner_id	The ID of the user
	 * @param string $owner_firstname	The new firstname for the user
	 * @param string $owner_surname	The new surname for the user
	 * @throws Exception
	 * @return unknown
	 */
	public static function rename($owner_id, $owner_firstname, $owner_surname) {
		$owner = self::get($owner_id);
		$owner_firstname = trim($owner_firstname);
		$owner_surname = trim($owner_surname);
		if($owner_firstname == "") {
			throw new Exception("Firstname cannot be blank");
		}
		
		if($owner_surname == "") {
			throw new Exception("Surname cannot be blank");
		}
		
		$owner -> owner_firstname = $owner_firstname;
		$owner -> owner_surname = $owner_surname;
		$owner -> update();
		
		/* ActionQueue */
		foreach($owner -> list_Account as $account) {
			ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctUpdate', $account -> account_login, $account -> account_login, $account -> AccountOwner -> Ou -> ou_name);
		}
		return $owner;
	}

	/**
	 * Delete a user
	 * 
	 * @param integer $owner_id
	 */
	public static function delete($owner_id) {
		$owner = self::get($owner_id);
		
		foreach($owner -> list_Account as $account) {
			Account_api::delete($account -> account_id);
		}
		
		foreach($owner -> list_OwnerUserGroup as $oug) {
			$oug -> delete();
		}

		$owner -> delete();
	}
	
	/**
	 * Add a user to a group
	 * 
	 * @param int $owner_id
	 * @param int $group_id
	 * @throws Exception
	 */
	public static function addtogroup($owner_id, $group_id) {
		if(self::ismember($owner_id, $group_id)) { // This also checks that the user and group exist
			throw new Exception("The user is already in that group! (or is in a group that is)");
		}
		
		/* Load these (will throw exceptions if they don't exist) */
		$owner = self::get($owner_id);
		$group = USerGroup_api::get($group_id);

		$oug = new OwnerUserGroup_model();
		$oug -> owner_id = $owner_id;
		$oug -> group_id = $group_id;
		$oug -> insert();
		
		/* ActionQueue */
		foreach($owner -> list_Account as $account) {
			ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'grpJoin', $account -> account_login, $group -> group_cn);
		}
	}
	
	/**
	 * Remove the user from a group
	 * 
	 * @param int $owner_id
	 * @param int $group_id
	 * @throws Exception
	 */
	public static function rmfromgroup($owner_id, $group_id) {
		$owner = self::get($owner_id);
		$group = UserGroup_api::get($group_id);
		
		if(!$oug = OwnerUserGroup_model::get($owner_id, $group_id)) {
			throw new Exception("User is not in that group");
		}
		
		$oug -> delete();
		
		/* ActionQueue */
		foreach($owner -> list_Account as $account) {
			ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'grpLeave', $account -> account_login, $group -> group_cn);
		}
	}
	
	/**
	 * Tests whether a user is in a given group
	 * 
	 * @param int $owner_id
	 * @param int $group_id
	 * @return boolean
	 */
	public static function ismember($owner_id, $group_id) {
		$owner = self::get($owner_id);
		$group = UserGroup_api::get($group_id);
		
		if($oug = OwnerUserGroup_model::get($owner_id, $group_id)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Return the AccountOwner associated with a login, if it is unique.
	 * This can be hit-and-miss, and should be used when accepting human input, rather than as part of a script,
	 * where you would use Account_model::get_by_.... with enough information to lookup using a login name uniquely.
	 * 
	 * @param unknown_type $account_login
	 * @throws Exception
	 */
	public static function searchLogin($account_login) {
		$logins = Account_model::searchLogin($account_login);
		if(count($logins) == 0) {
			throw new Exception("No accounts found");
		} else if(count($logins) > 1) {
			throw new Exception("Multiple accounts have that login");			
		}
		
		return self::get($logins[0] -> owner_id);
	}
}
?>