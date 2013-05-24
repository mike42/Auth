<?php

/**
 * Provides an interface for working with individual accounts (logins).
 */
class Account_api {
	/**
	 * Load dependencies
	 */
	public static function init() {
		Auth::loadClass("Account_model");
		Auth::loadClass("AccountOwner_api");
		Auth::loadClass("ActionQueue_api");
	}

	/**
	 * Load an account by ID
	 *
	 * @param int $account_id The ID of the account to load
	 * @throws Exception
	 * @return unknown
	 */
	public static function get($account_id) {
		if(!$account = Account_model::get($account_id)) {
			throw new Exception("No such account");
		}
		return $account;
	}

	/**
	 * Create a new account for a user.
	 *
	 * @param int $owner_id The ID of the user who will own this account
	 * @param string $account_login The login name for the user account. Must be unique to this domain and service.
	 * @param string $account_domain The domain for the account (see ListDomain table). For most services, domain is not particularly important.
	 * @param string $service_id The Service for the account (see Service table)
	 * @throws Exception
	 * @return Account_model
	 */
	public static function create($owner_id, $account_login, $account_domain, $service_id) {
		$owner = AccountOwner_api::get($owner_id);
		$account_login = Auth::normaliseName($account_login);
		if($account_login == "") {
			throw new Exception("Username cannot be blank");
		}

		/* Check that the related keys exist, etc */
		if(!$domain = ListDomain_model::get($account_domain)) {
			throw new Exception("No such domain");
		}
		if(!$service = Service_model::get($service_id)) {
			throw new Exception("No such service");
		}
		if(!$sd = ListServiceDomain_model::get($service_id, $account_domain)) {
			throw new Exception("That service is not available on the selected domain");
		}

		/* Figure out target domain */
		if(!$sd = ListServiceDomain_model::get($service_id, $account_domain)) {
			throw new Exception("Service '$service_id' does not exist on domain '$account_domain'.");
		}
			
		/* Look for services which are secondary on this domain */
		$d = $account_domain;
		if($sd -> sd_secondary == '1') {
			$d = $sd -> Service -> service_domain;
		}

		/* Look for username clashes */
		if($account = Account_model::get_by_account_login($account_login, $service_id, $d)) {
			throw new Exception("Login name '$account_login' is already in use on '$service_id' ($d).");
		}

		if($account = Account_model::get_by_service_owner_unique($service_id, $owner_id)) {
			throw new Exception("This person already has an account on '$service_id'.");
		}

		/* Create the account */
		$account = new Account_model();
		$account -> owner_id = $owner -> owner_id;
		$account -> account_login = $account_login;
		$account -> account_enabled = 1;
		$account -> account_domain = $d;
		$account -> service_id = $service -> service_id;
		$account -> account_enabled = 1;
		$account -> account_id = $account -> insert();

		/* ActionQueue */
		ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctCreate', $account -> account_login, $owner -> Ou -> ou_name, $owner -> owner_firstname,  $owner -> owner_surname);

		foreach($owner -> list_OwnerUserGroup as $oug) {
			ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'grpJoin', $account -> account_login, $oug -> UserGroup -> group_cn);
		}

		return $account;
	}

	/**
	 * Delete a single user account
	 *
	 * @param int $account_id The ID of the account to delete
	 */
	public static function delete($account_id) {
		$account = self::get($account_id);

		ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctDelete', $account -> account_login);

		/* This one is straightforward */
		$account -> delete();

		return $account;
	}

	/**
	 * Enable a user account
	 *
	 * @param int $account_id The ID of the account to enable
	 * @throws Exception
	 * @return unknown
	 */
	public static function enable($account_id) {
		$account = self::get($account_id);

		if((int)$account -> account_enabled == 1) {
			throw new Exception("The account is already enabled");
		}
		$account -> account_enabled = 1;
		$account -> update();

		ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctEnable', $account -> account_login);
		return $account;
	}

	/**
	 * Disable a user account
	 *
	 * @param int $account_id The ID of the account to disable
	 * @throws Exception
	 * @return unknown
	 */
	public static function disable($account_id) {
		$account = self::get($account_id);

		if((int)$account -> account_enabled == 0) {
			throw new Exception("The account is already disabled");
		}

		$account -> account_enabled = 0;
		$account -> update();

		ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctDisable', $account -> account_login);
		return $account;
	}

	/**
	 * Change a user account login
	 *
	 * @param int $account_id The ID of the account to change
	 * @param int $account_login The new login name for the user
	 * @throws Exception
	 */
	public static function rename($account_id, $account_login) {
		/* Check inputs */
		$account = self::get($account_id);
		$account_login = Auth::normaliseName($account_login);
		if($account_login == "") {
			throw new Exception("Username cannot be blank");
		}

		if($account_login == $account -> account_login) {
			/* Account name has not changed, do nothing */
			return $account;
		}

		if($a = Account_model::get_by_account_login($account_login, $account -> service_id, $account -> account_domain)) {
			/* Wont create duplicate account */
			throw new Exception("Login name '$account_login' is already in use on '".$account -> service_id."' (".$account -> account_domain.").");
		}

		ActionQueue_api::submit($account -> service_id, $account -> account_domain, 'acctUpdate', $account_login, $account -> account_login);

		$account -> account_login = $account_login;
		$account -> update();
		return $account;
	}
}