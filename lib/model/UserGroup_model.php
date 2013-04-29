<?php
class UserGroup_model {
	/* Fields */
	public $group_id;
	public $group_cn;
	public $group_name;
	public $ou_id;

	/* Referenced tables */
	public $Ou;

	/* Tables which reference this */
	public $list_OwnerUserGroup       = array();
	public $list_SubUserGroup         = array();

	/**
	 * Load all related models.
	*/
	public static function init() {
		sjcAuth::loadClass("Database");
		sjcAuth::loadClass("Ou_model");
		sjcAuth::loadClass("OwnerUserGroup_model");
		sjcAuth::loadClass("SubUserGroup_model");
	}

	/**
	 * Create new UserGroup based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function UserGroup_model(array $row = array()) {
		$this -> group_id   = isset($row['group_id'])   ? $row['group_id']  : '';
		$this -> group_cn   = isset($row['group_cn'])   ? $row['group_cn']  : '';
		$this -> group_name = isset($row['group_name']) ? $row['group_name']: '';
		$this -> ou_id      = isset($row['ou_id'])      ? $row['ou_id']     : '';

		/* Fields from related tables */
		$this -> Ou = new Ou_model($row);
	}

	public static function get($group_id) {
		$sql = "SELECT * FROM UserGroup LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id WHERE UserGroup.group_id='%s'";
		$res = Database::retrieve($sql, array($group_id));
		if($row = Database::get_row($res)) {
			return new UserGroup_model($row);
		}
		return false;
	}

	public static function get_by_group_cn($group_cn) {
		$sql = "SELECT * FROM UserGroup LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id WHERE UserGroup.group_cn='%s'";
		$res = Database::retrieve($sql, array($group_cn));
		if($row = Database::get_row($res)) {
			return new UserGroup_model($row);
		}
		return false;
	}

	public static function list_by_ou_id($ou_id) {
		$sql = "SELECT * FROM UserGroup LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id WHERE UserGroup.ou_id='%s';";
		$res = Database::retrieve($sql, array($ou_id));
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
		$sql = "INSERT INTO UserGroup(group_cn, group_name, ou_id) VALUES ('%s', '%s', '%s');";
		return Database::insert($sql, array($this -> group_cn, $this -> group_name, $this -> ou_id));
	}

	public function update() {
		$sql = "UPDATE UserGroup SET group_cn ='%s', group_name ='%s', ou_id ='%s' WHERE group_id ='%s';";
		return Database::update($sql, array($this -> group_cn, $this -> group_name, $this -> ou_id, $this -> group_id));
	}

	public function delete() {
		$sql = "DELETE FROM UserGroup WHERE group_id ='%s';";
		return Database::delete($sql, array($this -> group_id));
	}
}
?>