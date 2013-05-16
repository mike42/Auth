<?php

/**
 * Provides an interface for working with individual accounts (logins).
 */
class Account_api {
	public static function init() {
		Auth::loadClass("Account_model");
		Auth::loadClass("AccountOwner_api");
	}
	
	public static function get($account_id) {
		if(!$account = Account_model::get($account_id)) {
			throw new Exception("No such account");
		}
		return $account;		
	}
	
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
		
		// TODO: ActionQueue
		
		return $account;
	}

	
	public static function delete($account_id) {
		$account = self::get($account_id);
		
		/* This one is straightforward */
		$account -> delete();
		
		// TODO: ActionQueue
		
		return $account;
	}
	
	public static function enable($account_id) {
		$account = self::get($account_id);
		
		if((int)$account -> account_enabled == 1) {
			throw new Exception("The account is already enabled");
		}
		$account -> account_enabled = 1;
		$account -> update();
		
		// TODO: ActionQueue
		
		return $account;
	}
	
	public static function disable($account_id) {
		$account = self::get($account_id);
		
		if((int)$account -> account_enabled == 0) {
			throw new Exception("The account is already disabled");
		}
		
		$account -> account_enabled = 0;
		$account -> update();

		// TODO: ActionQueue
		return $account;
	}
	
	public static function rename($account_id, $account_login) {
		/* Check inputs */
		$account = self::get($account_id);
		$account_login = Auth::normaliseName($account_login);
		if($account_login == "") {
			throw new Exception("Username cannot be blank");
		}
		
		if($account_login == $account -> account_login) {
			/* Account name has not changed, do nothing */
			return;
		}
		
		if($a = Account_model::get_by_account_login($account_login, $account -> service_id, $account -> account_domain)) {
			/* Wont create duplicate account */
			throw new Exception("Login name '$account_login' is already in use on '".$account -> service_id."' (".$account -> account_domain.").");
		}
		
		$account -> account_login = $account_login;
		$account -> update();
		
		// TODO: ActionQueue
		return $account;
	}
}