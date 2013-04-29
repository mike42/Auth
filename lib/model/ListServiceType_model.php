<?php
class ListServiceType_model {
	/* Fields */
	public $service_type;

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