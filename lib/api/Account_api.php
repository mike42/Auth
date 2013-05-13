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
	
	
}