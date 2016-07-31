<?php
class Account_model {
	/* Fields */
	public $account_id;
	public $account_login;
	public $account_domain;
	public $service_id;
	public $owner_id;
	public $account_enabled;

	/* Referenced tables */
	public $Service;
	public $AccountOwner;
	public $ListDomain;

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("Service_model");
		Auth::loadClass("AccountOwner_model");
		Auth::loadClass("ListDomain_model");
	}

	/**
	 * Create new Account based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function Account_model(array $row = array()) {
		$this -> account_id      = isset($row['account_id'])      ? $row['account_id']     : '';
		$this -> account_login   = isset($row['account_login'])   ? $row['account_login']  : '';
		$this -> account_domain  = isset($row['account_domain'])  ? $row['account_domain'] : '';
		$this -> service_id      = isset($row['service_id'])      ? $row['service_id']     : '';
		$this -> owner_id        = isset($row['owner_id'])        ? $row['owner_id']       : '';
		$this -> account_enabled = isset($row['account_enabled']) ? $row['account_enabled']: '';

		/* Fields from related tables */
		$this -> Service = new Service_model($row);
		$this -> AccountOwner = new AccountOwner_model($row);
		$this -> ListDomain = new ListDomain_model($row);
	}

	public static function get($account_id) {
		$sql = "SELECT * FROM Account LEFT JOIN Service ON Account.service_id = Service.service_id LEFT JOIN AccountOwner ON Account.owner_id = AccountOwner.owner_id LEFT JOIN ListDomain ON Account.account_domain = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN Ou ON AccountOwner.ou_id = Ou.ou_id WHERE Account.account_id='%s'";
		$res = Database::retrieve($sql, array($account_id));
		if($row = Database::get_row($res)) {
			return new Account_model($row);
		}
		return false;
	}

	public static function get_by_service_owner_unique($service_id, $owner_id) {
		$sql = "SELECT * FROM Account LEFT JOIN Service ON Account.service_id = Service.service_id LEFT JOIN AccountOwner ON Account.owner_id = AccountOwner.owner_id LEFT JOIN ListDomain ON Account.account_domain = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN Ou ON AccountOwner.ou_id = Ou.ou_id WHERE Account.service_id='%s' AND Account.owner_id='%s'";
		$res = Database::retrieve($sql, array($service_id, $owner_id));
		if($row = Database::get_row($res)) {
			return new Account_model($row);
		}
		return false;
	}

	public static function get_by_account_login($account_login, $service_id, $account_domain) {
		$sql = "SELECT * FROM Account LEFT JOIN Service ON Account.service_id = Service.service_id LEFT JOIN AccountOwner ON Account.owner_id = AccountOwner.owner_id LEFT JOIN ListDomain ON Account.account_domain = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN Ou ON AccountOwner.ou_id = Ou.ou_id WHERE Account.account_login='%s' AND Account.service_id='%s' AND Account.account_domain='%s'";
		$res = Database::retrieve($sql, array($account_login, $service_id, $account_domain));
		if($row = Database::get_row($res)) {
			return new Account_model($row);
		}
		return false;
	}

	public static function list_by_owner_id($owner_id) {
		$sql = "SELECT * FROM Account LEFT JOIN Service ON Account.service_id = Service.service_id LEFT JOIN AccountOwner ON Account.owner_id = AccountOwner.owner_id LEFT JOIN ListDomain ON Account.account_domain = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN Ou ON AccountOwner.ou_id = Ou.ou_id WHERE Account.owner_id='%s';";
		$res = Database::retrieve($sql, array($owner_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Account_model($row);
		}
		return $ret;
	}

	public static function list_by_service_id($service_id) {
		$sql = "SELECT * FROM Account LEFT JOIN Service ON Account.service_id = Service.service_id LEFT JOIN AccountOwner ON Account.owner_id = AccountOwner.owner_id LEFT JOIN ListDomain ON Account.account_domain = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN Ou ON AccountOwner.ou_id = Ou.ou_id WHERE Account.service_id='%s';";
		$res = Database::retrieve($sql, array($service_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Account_model($row);
		}
		return $ret;
	}

	public static function list_by_account_domain($account_domain) {
		$sql = "SELECT * FROM Account LEFT JOIN Service ON Account.service_id = Service.service_id LEFT JOIN AccountOwner ON Account.owner_id = AccountOwner.owner_id LEFT JOIN ListDomain ON Account.account_domain = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN Ou ON AccountOwner.ou_id = Ou.ou_id WHERE Account.account_domain='%s';";
		$res = Database::retrieve($sql, array($account_domain));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Account_model($row);
		}
		return $ret;
	}

	public function insert() {
		$sql = "INSERT INTO Account(account_login, account_domain, service_id, owner_id, account_enabled) VALUES ('%s', '%s', '%s', '%s', '%s');";
		return Database::insert($sql, array($this -> account_login, $this -> account_domain, $this -> service_id, $this -> owner_id, $this -> account_enabled));
	}

	public function update() {
		$sql = "UPDATE Account SET account_login ='%s', account_domain ='%s', service_id ='%s', owner_id ='%s', account_enabled ='%s' WHERE account_id ='%s';";
		return Database::update($sql, array($this -> account_login, $this -> account_domain, $this -> service_id, $this -> owner_id, $this -> account_enabled, $this -> account_id));
	}

	public function delete() {
		$sql = "DELETE FROM Account WHERE account_id ='%s';";
		return Database::delete($sql, array($this -> account_id));
	}

	/* Non-generated functions */
	public static function search($term) {
		$sql = "SELECT DISTINCT Account.owner_id, account_login, owner_firstname, owner_surname " .
				"FROM Account " .
				"JOIN ListDomain ON ListDomain.domain_id = Account.account_domain " .
				"JOIN AccountOwner ON Account.owner_id = AccountOwner.owner_id " .
				"WHERE account_login LIKE \"%%%s%%\" OR owner_firstname LIKE \"%%%s%%\" OR owner_surname LIKE \"%%%s%%\" " .
				"ORDER BY domain_name, owner_surname, owner_firstname, account_login " .
				"LIMIT 0 , 20;";
		$term = str_replace("%", "\"%", $term);
		$res = Database::retrieve($sql, array($term, $term, $term));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Account_model($row);
		}
		return $ret;
	}
	
	public static function searchLogin($account_login) {
		$sql = "SELECT DISTINCT owner_id, account_login FROM `Account` WHERE `account_login` = '%s'";
		$res = Database::retrieve($sql, array($account_login));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Account_model($row);
		}
		return $ret;
	}
	
	public static function search_by_service_domain($term, $service_id, $domain_id) {
		$sql = "SELECT DISTINCT Account.owner_id, account_login, owner_firstname, owner_surname " .
				"FROM Account " .
				"JOIN ListDomain ON ListDomain.domain_id = Account.account_domain " .
				"JOIN AccountOwner ON Account.owner_id = AccountOwner.owner_id " .
				"WHERE (account_login LIKE \"%%%s%%\" OR owner_firstname LIKE \"%%%s%%\" OR owner_surname LIKE \"%%%s%%\") " .
				"AND Account.service_id = '%s' AND Account.account_domain = '%s' " .
				"ORDER BY domain_name, owner_surname, owner_firstname, account_login " .
				"LIMIT 0 , 20;";
		$term = str_replace("%", "\"%", $term);
		$res = Database::retrieve($sql, array($term, $term, $term, $service_id, $domain_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Account_model($row);
		}
		return $ret;
	}
}
?>
