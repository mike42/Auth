<?php 

require_once(dirname(__FILE__) . "/account_service.php");

class gapps_service extends account_service {
	private $service;

	function __construct(Service_model $service) {
		$this -> service = $service;
	}
	
	function accountCreate(Account_model $a) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function accountDelete($account_login, ListDomain_model $account_domain) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function accountUpdate(Account_model $a, $account_old_login) {
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
	
	function groupCreate(UserGroup_model $g) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function groupDelete($group_cn, $group_domain) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function groupJoin(Account_model $a, UserGroup_model $g) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function groupLeave(Account_model $a, UserGroup_model $g) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function groupAddChild(UserGroup_model $parent, UserGroup_model $child) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function groupDelChild(UserGroup_model $parent, UserGroup_model $child) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function groupMove(UserGroup_model $g, Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function groupRename(UserGroup_model $g, $ug_old_cn) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function recursiveSearch(Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function ouCreate(Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function ouDelete($ou_name, ListDomain_model $d) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function ouMove(Ou_model $o, Ou_model $parent) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function ouRename($ou_old_name, Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
}
?>