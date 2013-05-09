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
		Auth::loadClass("Account_model");
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
		throw new Exception("unimplemented");
	}
}

?>