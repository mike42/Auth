<?php 

require_once(dirname(__FILE__) . "/account_service.php");

class ldap_service extends account_service {
	private $service;
	private $ldap_url;
	private $ldap_user;
	private $ldap_root;
	private $ldap_pass;
	
	/**
	 * Construct a new LDAP service object
	 * 
	 * @param string $ldap_url The URL of the ldap server. Looks like "ldaps://<hostname>:<port>".
	 * @param string $ldap_user The username to log in as. Usually "cn=admin,dc=..,dc=.." or "Administrator@<domain name>" for Active Directory.
	 * @param string $root The root of the domain. Looks like "dc=example,dc=com".
	 * @param string $ldap_pass The password to use when logging in to this server.
	 */
	function __construct(Service_model $service) {
		$this -> ldap_url = $service -> service_address;
		$this -> ldap_user = $service -> service_username;
		$this -> ldap_root = $service -> service_root;
		$this -> ldap_pass = $service -> service_password;
	}
	
	function accountCreate(Account_model $a) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function accountDelete($account_login, ListDomain_model $account_domain) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function accountUpdate(Account_model $a, $account_old_login, $owner_firstname, $owner_surname) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function accountDisable(Account_model $a) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function accountEnable(Account_model $a) {
		// TODO
		throw new Exception("Unimplemented");
	}

	function accountRelocate(Account_model $a, Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function accountPassword(Account_model $a, $p) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function recursiveSearch(Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
}
?>