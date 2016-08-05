<?php
namespace Auth\model;

use Auth\Auth;
use Auth\misc\Database;
use Auth\model\ListDomain_model;
use Auth\model\Ou_model;
use Auth\model\OwnerUserGroup_model;
use Auth\model\SubUserGroup_model;
use Auth\model\UserGroup_model;

class UserGroup_model {
	/* Fields */
	public $group_id;
	public $group_cn;
	public $group_name;
	public $ou_id;
	public $group_domain;

	/* Referenced tables */
	public $Ou;
	public $ListDomain;

	/* Tables which reference this */
	public $list_OwnerUserGroup       = array();
	public $list_SubUserGroup         = array();

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("Ou_model");
		Auth::loadClass("ListDomain_model");
		Auth::loadClass("OwnerUserGroup_model");
		Auth::loadClass("SubUserGroup_model");
	}

	/**
	 * Create new UserGroup based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function __construct(array $row = array()) {
		$this -> group_id     = isset($row['group_id'])     ? $row['group_id']    : '';
		$this -> group_cn     = isset($row['group_cn'])     ? $row['group_cn']    : '';
		$this -> group_name   = isset($row['group_name'])   ? $row['group_name']  : '';
		$this -> ou_id        = isset($row['ou_id'])        ? $row['ou_id']       : '';
		$this -> group_domain = isset($row['group_domain']) ? $row['group_domain']: '';

		/* Fields from related tables */
		$this -> Ou = new Ou_model($row);
		$this -> ListDomain = new ListDomain_model($row);
	}

	public static function get($group_id) {
		$sql = "SELECT * FROM UserGroup LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id LEFT JOIN ListDomain ON UserGroup.group_domain = ListDomain.domain_id WHERE UserGroup.group_id='%s'";
		$res = Database::retrieve($sql, array($group_id));
		if($row = Database::get_row($res)) {
			return new UserGroup_model($row);
		}
		return false;
	}

	public static function get_by_group_cn($group_cn) {
		$sql = "SELECT * FROM UserGroup LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id LEFT JOIN ListDomain ON UserGroup.group_domain = ListDomain.domain_id WHERE UserGroup.group_cn='%s'";
		$res = Database::retrieve($sql, array($group_cn));
		if($row = Database::get_row($res)) {
			return new UserGroup_model($row);
		}
		return false;
	}

	public static function list_by_ou_id($ou_id) {
		$sql = "SELECT * FROM UserGroup LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id LEFT JOIN ListDomain ON UserGroup.group_domain = ListDomain.domain_id WHERE UserGroup.ou_id='%s' ORDER BY UserGroup.group_name, UserGroup.group_id;";
		$res = Database::retrieve($sql, array($ou_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new UserGroup_model($row);
		}
		return $ret;
	}

	public static function list_by_group_domain($group_domain) {
		$sql = "SELECT * FROM UserGroup LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id LEFT JOIN ListDomain ON UserGroup.group_domain = ListDomain.domain_id WHERE UserGroup.group_domain='%s' ORDER BY UserGroup.group_name, UserGroup.group_id;";
		$res = Database::retrieve($sql, array($group_domain));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new UserGroup_model($row);
		}
		return $ret;
	}

	public function populate_list_OwnerUserGroup() {
		$this -> list_OwnerUserGroup = OwnerUserGroup_model::list_by_group_id($this -> group_id);
	}

	public function populate_list_SubUserGroup() {
		$this -> list_SubUserGroup = SubUserGroup_model::list_by_group_id($this -> group_id);
	}

	public function insert() {
		$sql = "INSERT INTO UserGroup(group_cn, group_name, ou_id, group_domain) VALUES ('%s', '%s', '%s', '%s');";
		return Database::insert($sql, array($this -> group_cn, $this -> group_name, $this -> ou_id, $this -> group_domain));
	}

	public function update() {
		$sql = "UPDATE UserGroup SET group_cn ='%s', group_name ='%s', ou_id ='%s', group_domain ='%s' WHERE group_id ='%s';";
		return Database::update($sql, array($this -> group_cn, $this -> group_name, $this -> ou_id, $this -> group_domain, $this -> group_id));
	}

	public function delete() {
		$sql = "DELETE FROM UserGroup WHERE group_id ='%s';";
		return Database::delete($sql, array($this -> group_id));
	}

	/* Non-generated functions */
	public static function search($term) {
		$sql = "SELECT * FROM UserGroup " .
				"WHERE UserGroup.group_cn LIKE \"%%%s%%\" OR UserGroup.group_name LIKE \"%%%s%%\" " .
				"ORDER BY group_name " .
				"LIMIT 0 , 20;";
		$term = str_replace("%", "\"%", $term);
		$res = Database::retrieve($sql, array($term, $term));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new UserGroup_model($row);
		}
		return $ret;
	}
}
?>
