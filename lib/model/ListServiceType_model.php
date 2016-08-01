<?php
namespace Auth\model;

use Auth\Auth;
use Auth\misc\Database;
use Auth\model\ListServiceType_model;
use Auth\model\Service_model;

class ListServiceType_model {
	/* Fields */
	public $service_type;

	/* Tables which reference this */
	public $list_Service              = array();

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("Service_model");
	}

	/**
	 * Create new ListServiceType based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function ListServiceType_model(array $row = array()) {
		$this -> service_type = isset($row['service_type']) ? $row['service_type']: '';
	}

	public static function get($service_type) {
		$sql = "SELECT * FROM ListServiceType WHERE ListServiceType.service_type='%s'";
		$res = Database::retrieve($sql, array($service_type));
		if($row = Database::get_row($res)) {
			return new ListServiceType_model($row);
		}
		return false;
	}

	public function populate_list_Service() {
		$this -> list_Service = Service_model::list_by_service_type($this -> service_type);
	}

	public function insert() {
		$sql = "INSERT INTO ListServiceType(service_type) VALUES ('%s');";
		return Database::insert($sql, array($this -> service_type));
	}

	public function update() {
		$sql = "UPDATE ListServiceType SET  WHERE service_type ='%s';";
		return Database::update($sql, array($this -> service_type));
	}

	public function delete() {
		$sql = "DELETE FROM ListServiceType WHERE service_type ='%s';";
		return Database::delete($sql, array($this -> service_type));
	}
}
?>