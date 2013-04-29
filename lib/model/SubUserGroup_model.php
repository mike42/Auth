<?php
class SubUserGroup_model {
	/* Fields */
	public $parent_group_id;
	public $group_id;

	/* Referenced tables */
	public $UserGroup;

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("UserGroup_model");
	}

	/**
	 * Create new SubUserGroup based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function SubUserGroup_model(array $row = array()) {
		$this -> parent_group_id = isset($row['parent_group_id']) ? $row['parent_group_id']: '';
		$this -> group_id        = isset($row['group_id'])        ? $row['group_id']       : '';

		/* Fields from related tables */
		$this -> UserGroup = new UserGroup_model($row);
	}

	public static function get($parent_group_id, $group_id) {
		$sql = "SELECT * FROM SubUserGroup LEFT JOIN UserGroup ON SubUserGroup.group_id = UserGroup.group_id LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id WHERE SubUserGroup.parent_group_id='%s' AND SubUserGroup.group_id='%s'";
		$res = Database::retrieve($sql, array($parent_group_id, $group_id));
		if($row = Database::get_row($res)) {
			return new SubUserGroup_model($row);
		}
		return false;
	}

	public static function list_by_parent_group_id($parent_group_id) {
		$sql = "SELECT * FROM SubUserGroup LEFT JOIN UserGroup ON SubUserGroup.group_id = UserGroup.group_id LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id WHERE SubUserGroup.parent_group_id='%s';";
		$res = Database::retrieve($sql, array($parent_group_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new SubUserGroup_model($row);
		}
		return $ret;
	}

	public static function list_by_group_id($group_id) {
		$sql = "SELECT * FROM SubUserGroup LEFT JOIN UserGroup ON SubUserGroup.group_id = UserGroup.group_id LEFT JOIN Ou ON UserGroup.ou_id = Ou.ou_id WHERE SubUserGroup.group_id='%s';";
		$res = Database::retrieve($sql, array($group_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new SubUserGroup_model($row);
		}
		return $ret;
	}

	public function insert() {
		$sql = "INSERT INTO SubUserGroup(parent_group_id, group_id) VALUES ('%s', '%s');";
		return Database::insert($sql, array($this -> parent_group_id, $this -> group_id));
	}

	public function update() {
		$sql = "UPDATE SubUserGroup SET  WHERE parent_group_id ='%s' AND group_id ='%s';";
		return Database::update($sql, array($this -> parent_group_id, $this -> group_id));
	}

	public function delete() {
		$sql = "DELETE FROM SubUserGroup WHERE parent_group_id ='%s' AND group_id ='%s';";
		return Database::delete($sql, array($this -> parent_group_id, $this -> group_id));
	}
}
?>