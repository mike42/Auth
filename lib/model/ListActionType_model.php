<?php
use Auth\Auth;

class ListActionType_model {
	/* Fields */
	public $action_type;

	/* Tables which reference this */
	public $list_ActionQueue          = array();

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("ActionQueue_model");
	}

	/**
	 * Create new ListActionType based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function ListActionType_model(array $row = array()) {
		$this -> action_type = isset($row['action_type']) ? $row['action_type']: '';
	}

	public static function get($action_type) {
		$sql = "SELECT * FROM ListActionType WHERE ListActionType.action_type='%s'";
		$res = Database::retrieve($sql, array($action_type));
		if($row = Database::get_row($res)) {
			return new ListActionType_model($row);
		}
		return false;
	}

	public function populate_list_ActionQueue() {
		$this -> list_ActionQueue = ActionQueue_model::list_by_action_type($this -> action_type);
	}

	public function insert() {
		$sql = "INSERT INTO ListActionType(action_type) VALUES ('%s');";
		return Database::insert($sql, array($this -> action_type));
	}

	public function update() {
		$sql = "UPDATE ListActionType SET  WHERE action_type ='%s';";
		return Database::update($sql, array($this -> action_type));
	}

	public function delete() {
		$sql = "DELETE FROM ListActionType WHERE action_type ='%s';";
		return Database::delete($sql, array($this -> action_type));
	}
}
?>