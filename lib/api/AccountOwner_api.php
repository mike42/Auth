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
		Auth::loadClass("Ou_api");
		Auth::loadClass("Account_model");
		Auth::loadClass("ListServiceDomain_model");
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
		$account_login = trim($account_login);
		if($owner_firstname == "") {
			throw new Exception("No firstname specified");
		}
		if($owner_surname == "") {
			throw new Exception("No surname specified");
		}
		if($owner_surname == "") {
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
			$account -> account_id = $account -> insert();

			// TODO: ActionQueue
		}

		$owner -> populate_List_Account();
		return $owner;		
	}
	
	/**
	 * Get a user by ID, throwing an exception if the account does not exist.
	 * 
	 * @param string $owner_id
	 */
	function get($owner_id) {
		if(!$owner = AccountOwner_model::get($owner_id)) {
			throw new Exception("Account owner not found");
		}
		$owner -> populate_List_Account();
		$owner -> populate_List_OwnerUserGroup();
		return $owner;
	}
	
	public static function pwreset($owner_id, $password) {
		$owner = self::get($owner_id);
		// TODO: Verify against services
		// TODO: ActionQueue	
		throw new Exception("Unimplemented");
		
		return $owner;
	}
	
	public static function move($owner_id, $ou_id) {
		$owner = self::get($owner_id);
		$ou = Ou_api::get($ou_id);
		
		if($owner -> ou_id == $ou -> ou_id) {
			/* Nothing to do. */
			return;
		}
		
		/* Update */
		$owner -> ou_id = $ou -> ou_id;
		$owner -> update();
		
		// TODO: ActionQueue
		
		return $owner;
	}
	
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
		
		// TODO: ActionQueue
		
		return $owner;
	}
	
	public static function delete($owner_id) {
		$owner = self::get($owner_id);
		
		// TODO: ActionQueue
		foreach($owner -> list_Account as $account) {
			$account -> delete();
		}
		
		foreach($owner -> list_OwnerUserGroup as $oug) {
			$oug -> delete();
		}
		
		$owner -> delete();
	}
	
	public static function addtogroup($owner_id, $group_id) {
		if(self::ismember($owner_id, $group_id)) { // This also checks that the user and group exist
			throw new Exception("The user is already in that group! (or is in a group that is)");
		}

		$oug = new OwnerUserGroup_model();
		$oug -> owner_id = $owner_id;
		$oug -> group_id = $group_id;
		$oug -> insert();
		
		// TODO: ActionQueue
		
	}
	
	public static function rmfromgroup($owner_id, $group_id) {
		$owner = self::get($owner_id);
		$group = UserGroup_api::get($group_id);
		
		if(!$oug = OwnerUserGroup_model::get($owner_id, $group_id)) {
			throw new Exception("User is not in that group");
		}
		
		$oug -> delete();
		
		// TODO: ActionQueue

	}
	
	/**
	 * Tests whether a user is in a given group
	 * 
	 * @param unknown_type $owner_id
	 * @param unknown_type $group_id
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
}
?>