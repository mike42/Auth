<?php 

require_once(dirname(__FILE__) . "/ldap_service.php");

class ad_service extends ldap_service {
	
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