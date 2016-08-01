<?php
namespace Auth\model;

use Auth\Auth;
use Auth\misc\Database;
use Auth\model\Account_model;
use Auth\model\ActionQueue_model;
use Auth\model\ListDomain_model;
use Auth\model\ListServiceDomain_model;
use Auth\model\Service_model;
use Auth\model\UserGroup_model;

class ListDomain_model {
	/* Fields */
	public $domain_id;
	public $domain_name;
	public $domain_enabled;

	/* Tables which reference this */
	public $list_Account              = array();
	public $list_ActionQueue          = array();
	public $list_ListServiceDomain    = array();
	public $list_Service              = array();
	public $list_UserGroup            = array();

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("Account_model");
		Auth::loadClass("ActionQueue_model");
		Auth::loadClass("ListServiceDomain_model");
		Auth::loadClass("Service_model");
		Auth::loadClass("UserGroup_model");
	}

	/**
	 * Create new ListDomain based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function ListDomain_model(array $row = array()) {
		$this -> domain_id      = isset($row['domain_id'])      ? $row['domain_id']     : '';
		$this -> domain_name    = isset($row['domain_name'])    ? $row['domain_name']   : '';
		$this -> domain_enabled = isset($row['domain_enabled']) ? $row['domain_enabled']: '';
	}

	public static function get($domain_id) {
		$sql = "SELECT * FROM ListDomain WHERE ListDomain.domain_id='%s'";
		$res = Database::retrieve($sql, array($domain_id));
		if($row = Database::get_row($res)) {
			return new ListDomain_model($row);
		}
		return false;
	}

	public static function list_by_domain_enabled($domain_enabled) {
		$sql = "SELECT * FROM ListDomain WHERE ListDomain.domain_enabled='%s';";
		$res = Database::retrieve($sql, array($domain_enabled));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new ListDomain_model($row);
		}
		return $ret;
	}

	public function populate_list_Account() {
		$this -> list_Account = Account_model::list_by_account_domain($this -> domain_id);
	}

	public function populate_list_ActionQueue() {
		$this -> list_ActionQueue = ActionQueue_model::list_by_domain_id($this -> domain_id);
	}

	public function populate_list_ListServiceDomain() {
		$this -> list_ListServiceDomain = ListServiceDomain_model::list_by_domain_id($this -> domain_id);
	}

	public function populate_list_Service() {
		$this -> list_Service = Service_model::list_by_service_domain($this -> domain_id);
	}

	public function populate_list_UserGroup() {
		$this -> list_UserGroup = UserGroup_model::list_by_group_domain($this -> domain_id);
	}

	public function insert() {
		$sql = "INSERT INTO ListDomain(domain_id, domain_name, domain_enabled) VALUES ('%s', '%s', '%s');";
		return Database::insert($sql, array($this -> domain_id, $this -> domain_name, $this -> domain_enabled));
	}

	public function update() {
		$sql = "UPDATE ListDomain SET domain_name ='%s', domain_enabled ='%s' WHERE domain_id ='%s';";
		return Database::update($sql, array($this -> domain_name, $this -> domain_enabled, $this -> domain_id));
	}

	public function delete() {
		$sql = "DELETE FROM ListDomain WHERE domain_id ='%s';";
		return Database::delete($sql, array($this -> domain_id));
	}
}
?>