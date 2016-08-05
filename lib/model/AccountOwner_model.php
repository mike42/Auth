<?php
namespace Auth\model;

use Auth\Auth;
use Auth\misc\Database;
use Auth\model\Account_model;
use Auth\model\AccountOwner_model;
use Auth\model\Ou_model;
use Auth\model\OwnerUserGroup_model;

class AccountOwner_model {
	/* Fields */
	public $owner_id;
	public $owner_firstname;
	public $owner_surname;
	public $ou_id;

	/* Referenced tables */
	public $Ou;

	/* Tables which reference this */
	public $list_Account              = array();
	public $list_OwnerUserGroup       = array();

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("Ou_model");
		Auth::loadClass("Account_model");
		Auth::loadClass("OwnerUserGroup_model");
	}

	/**
	 * Create new AccountOwner based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function __construct(array $row = array()) {
		$this -> owner_id        = isset($row['owner_id'])        ? $row['owner_id']       : '';
		$this -> owner_firstname = isset($row['owner_firstname']) ? $row['owner_firstname']: '';
		$this -> owner_surname   = isset($row['owner_surname'])   ? $row['owner_surname']  : '';
		$this -> ou_id           = isset($row['ou_id'])           ? $row['ou_id']          : '';

		/* Fields from related tables */
		$this -> Ou = new Ou_model($row);
	}

	public static function get($owner_id) {
		$sql = "SELECT * FROM AccountOwner LEFT JOIN Ou ON AccountOwner.ou_id = Ou.ou_id WHERE AccountOwner.owner_id='%s'";
		$res = Database::retrieve($sql, array($owner_id));
		if($row = Database::get_row($res)) {
			return new AccountOwner_model($row);
		}
		return false;
	}

	public static function list_by_ou_id($ou_id) {
		$sql = "SELECT * FROM AccountOwner LEFT JOIN Ou ON AccountOwner.ou_id = Ou.ou_id WHERE AccountOwner.ou_id='%s' ORDER BY owner_surname, owner_firstname, owner_id;";
		$res = Database::retrieve($sql, array($ou_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new AccountOwner_model($row);
		}
		return $ret;
	}

	public function populate_list_Account() {
		$this -> list_Account = Account_model::list_by_owner_id($this -> owner_id);
	}

	public function populate_list_OwnerUserGroup() {
		$this -> list_OwnerUserGroup = OwnerUserGroup_model::list_by_owner_id($this -> owner_id);
	}

	public function insert() {
		$sql = "INSERT INTO AccountOwner(owner_firstname, owner_surname, ou_id) VALUES ('%s', '%s', '%s');";
		return Database::insert($sql, array($this -> owner_firstname, $this -> owner_surname, $this -> ou_id));
	}

	public function update() {
		$sql = "UPDATE AccountOwner SET owner_firstname ='%s', owner_surname ='%s', ou_id ='%s' WHERE owner_id ='%s';";
		return Database::update($sql, array($this -> owner_firstname, $this -> owner_surname, $this -> ou_id, $this -> owner_id));
	}

	public function delete() {
		$sql = "DELETE FROM AccountOwner WHERE owner_id ='%s';";
		return Database::delete($sql, array($this -> owner_id));
	}
}
?>