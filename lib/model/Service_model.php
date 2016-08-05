<?php
namespace Auth\model;

use Auth\Auth;
use Auth\misc\Database;
use Auth\model\Account_model;
use Auth\model\ActionQueue_model;
use Auth\model\ListDomain_model;
use Auth\model\ListServiceDomain_model;
use Auth\model\ListServiceType_model;
use Auth\model\Service_model;

class Service_model {
	/* Fields */
	public $service_id;
	public $service_name;
	public $service_enabled;
	public $service_type;
	public $service_address;
	public $service_username;
	public $service_password;
	public $service_domain;
	public $service_pwd_regex;
	public $service_root;

	/* Referenced tables */
	public $ListServiceType;
	public $ListDomain;

	/* Tables which reference this */
	public $list_Account              = array();
	public $list_ActionQueue          = array();
	public $list_ListServiceDomain    = array();

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("ListServiceType_model");
		Auth::loadClass("ListDomain_model");
		Auth::loadClass("Account_model");
		Auth::loadClass("ActionQueue_model");
		Auth::loadClass("ListServiceDomain_model");
	}

	/**
	 * Create new Service based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function __construct(array $row = array()) {
		$this -> service_id        = isset($row['service_id'])        ? $row['service_id']       : '';
		$this -> service_name      = isset($row['service_name'])      ? $row['service_name']     : '';
		$this -> service_enabled   = isset($row['service_enabled'])   ? $row['service_enabled']  : '';
		$this -> service_type      = isset($row['service_type'])      ? $row['service_type']     : '';
		$this -> service_address   = isset($row['service_address'])   ? $row['service_address']  : '';
		$this -> service_username  = isset($row['service_username'])  ? $row['service_username'] : '';
		$this -> service_password  = isset($row['service_password'])  ? $row['service_password'] : '';
		$this -> service_domain    = isset($row['service_domain'])    ? $row['service_domain']   : '';
		$this -> service_pwd_regex = isset($row['service_pwd_regex']) ? $row['service_pwd_regex']: '';
		$this -> service_root      = isset($row['service_root'])      ? $row['service_root']     : '';

		/* Fields from related tables */
		$this -> ListServiceType = new ListServiceType_model($row);
		$this -> ListDomain = new ListDomain_model($row);
	}

	public static function get($service_id) {
		$sql = "SELECT * FROM Service LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN ListDomain ON Service.service_domain = ListDomain.domain_id WHERE Service.service_id='%s'";
		$res = Database::retrieve($sql, array($service_id));
		if($row = Database::get_row($res)) {
			return new Service_model($row);
		}
		return false;
	}

	public static function list_by_service_enabled($service_enabled) {
		$sql = "SELECT * FROM Service LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN ListDomain ON Service.service_domain = ListDomain.domain_id WHERE Service.service_enabled='%s';";
		$res = Database::retrieve($sql, array($service_enabled));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Service_model($row);
		}
		return $ret;
	}

	public static function list_by_service_type($service_type) {
		$sql = "SELECT * FROM Service LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN ListDomain ON Service.service_domain = ListDomain.domain_id WHERE Service.service_type='%s';";
		$res = Database::retrieve($sql, array($service_type));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Service_model($row);
		}
		return $ret;
	}

	public static function list_by_service_domain($service_domain) {
		$sql = "SELECT * FROM Service LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type LEFT JOIN ListDomain ON Service.service_domain = ListDomain.domain_id WHERE Service.service_domain='%s';";
		$res = Database::retrieve($sql, array($service_domain));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Service_model($row);
		}
		return $ret;
	}

	public function populate_list_Account() {
		$this -> list_Account = Account_model::list_by_service_id($this -> service_id);
	}

	public function populate_list_ActionQueue() {
		$this -> list_ActionQueue = ActionQueue_model::list_by_service_id($this -> service_id);
	}

	public function populate_list_ListServiceDomain() {
		$this -> list_ListServiceDomain = ListServiceDomain_model::list_by_service_id($this -> service_id);
	}

	public function insert() {
		$sql = "INSERT INTO Service(service_id, service_name, service_enabled, service_type, service_address, service_username, service_password, service_domain, service_pwd_regex, service_root) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');";
		return Database::insert($sql, array($this -> service_id, $this -> service_name, $this -> service_enabled, $this -> service_type, $this -> service_address, $this -> service_username, $this -> service_password, $this -> service_domain, $this -> service_pwd_regex, $this -> service_root));
	}

	public function update() {
		$sql = "UPDATE Service SET service_name ='%s', service_enabled ='%s', service_type ='%s', service_address ='%s', service_username ='%s', service_password ='%s', service_domain ='%s', service_pwd_regex ='%s', service_root ='%s' WHERE service_id ='%s';";
		return Database::update($sql, array($this -> service_name, $this -> service_enabled, $this -> service_type, $this -> service_address, $this -> service_username, $this -> service_password, $this -> service_domain, $this -> service_pwd_regex, $this -> service_root, $this -> service_id));
	}

	public function delete() {
		$sql = "DELETE FROM Service WHERE service_id ='%s';";
		return Database::delete($sql, array($this -> service_id));
	}
}
?>