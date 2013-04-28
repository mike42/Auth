<?php 

require_once(dirname(__FILE__) . "/account_service.php");

class ldap_service extends account_service {
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
	function __construct(string $ldap_url, string $ldap_user, string $ldap_root, string $ldap_pass) {
		$this -> ldap_url = $ldap_url;
		$this -> ldap_user = $ldap_user;
		$this -> ldap_root = $ldap_root;
		$this -> ldap_pass = $ldap_pass;
	}
	
		
}
?>