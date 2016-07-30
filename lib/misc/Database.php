<?php
/**
 *	Wrapper for mySQL extension to manage connection and avoid injection.
 */
class Database {
	private static $conn; /* Database connection */
	private static $conf; /* Config */
	
	public static function init() {
		/* Get configuration for this class and connect to the database */
		self::$conf = Auth::getConfig(__CLASS__);
		Database::connect();
	}
	
	private static function connect() {
		self::$conn = new PDO("mysql:host=" . database::$conf['host'] . ";dbname=" . database::$conf['name'] . ";charset=utf8mb4", database::$conf['user'], database::$conf['password']);
	}

	public static function get_row($result) {
		if(!$result) {
			return false;
		}
		return $result -> fetch(PDO::FETCH_ASSOC);
	}
	
	public static function insert_id() {
		return Database::$conn -> lastInsertId();
	}
	
	public static function close() {
		/* Close connection */
		$this -> conn = null;
		return true;
	}
	
	public static function retrieve($query, array $arg) {
		return self::doQuery($query, $arg);
	}
	
	public static function insert($query, array $arg) {
		$res = self::doQuery($query, $arg);
		return self::insert_id();
	}
	
	public static function delete($query, array $arg) {
		$res = self::doQuery($query, $arg);
		return true;
	}
	
	public static function update($query, array $arg) {
		$res = self::doQuery($query, $arg);
		return true;
	}
	
	private static function doQuery($query, array $arg) {
		/* Query wrapper to be sure everything is escaped. All SQL must go through here! */
		$query = str_replace("'%s'", "?", $query);
		$stmt = self::$conn -> prepare($query);
		$stmt -> execute($arg);
		return $stmt;
	}

	public static function row_from_template($row, $template) {
		/* This copies an associative array from the database, copying only fields which exist in this template */
		$res = $template;
		foreach($row as $key => $val) {
			if(isset($res[$key])) {
				$res[$key] = $val;
			}
		}
		return $res;
	}	
}

?>
