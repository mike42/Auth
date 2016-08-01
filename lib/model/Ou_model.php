<?php
namespace Auth\model;

use Auth\Auth;
use Auth\misc\Database;
use Auth\model\AccountOwner_model;
use Auth\model\Ou_model;
use Auth\model\UserGroup_model;

class Ou_model {
	/* Fields */
	public $ou_id;
	public $ou_parent_id;
	public $ou_name;

	/* Referenced tables */
	public $Ou;

	/* Tables which reference this */
	public $list_AccountOwner         = array();
	public $list_Ou                   = array();
	public $list_UserGroup            = array();

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("Ou_model");
		Auth::loadClass("AccountOwner_model");
		Auth::loadClass("UserGroup_model");
	}

	/**
	 * Create new Ou based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function Ou_model(array $row = array()) {
		$this -> ou_id        = isset($row['ou_id'])        ? $row['ou_id']       : '';
		$this -> ou_parent_id = isset($row['ou_parent_id']) ? $row['ou_parent_id']: '';
		$this -> ou_name      = isset($row['ou_name'])      ? $row['ou_name']     : '';

		/* Fields from related tables */
		/* Self-reference excluded to prevent an infinite loop */
//		$this -> Ou = new Ou_model($row);
	}

	public static function get($ou_id) {
		$sql = "SELECT * FROM Ou WHERE Ou.ou_id='%s'";
		$res = Database::retrieve($sql, array($ou_id));
		if($row = Database::get_row($res)) {
			return new Ou_model($row);
		}
		return false;
	}

	public static function get_by_ou_name($ou_name) {
		$sql = "SELECT * FROM Ou WHERE Ou.ou_name='%s'";
		$res = Database::retrieve($sql, array($ou_name));
		if($row = Database::get_row($res)) {
			return new Ou_model($row);
		}
		return false;
	}

	public static function list_by_ou_parent_id($ou_parent_id) {
		$sql = "SELECT * FROM Ou WHERE Ou.ou_parent_id='%s';";
		$res = Database::retrieve($sql, array($ou_parent_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new Ou_model($row);
		}
		return $ret;
	}

	public function populate_list_AccountOwner() {
		$this -> list_AccountOwner = AccountOwner_model::list_by_ou_id($this -> ou_id);
	}

	public function populate_list_Ou() {
		$this -> list_Ou = Ou_model::list_by_ou_parent_id($this -> ou_id);
	}

	public function populate_list_UserGroup() {
		$this -> list_UserGroup = UserGroup_model::list_by_ou_id($this -> ou_id);
	}

	public function insert() {
		$sql = "INSERT INTO Ou(ou_parent_id, ou_name) VALUES ('%s', '%s');";
		return Database::insert($sql, array($this -> ou_parent_id, $this -> ou_name));
	}

	public function update() {
		$sql = "UPDATE Ou SET ou_parent_id ='%s', ou_name ='%s' WHERE ou_id ='%s';";
		return Database::update($sql, array($this -> ou_parent_id, $this -> ou_name, $this -> ou_id));
	}

	public function delete() {
		$sql = "DELETE FROM Ou WHERE ou_id ='%s';";
		return Database::delete($sql, array($this -> ou_id));
	}
}
?>