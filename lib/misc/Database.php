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
		if(!Database::connect()) {
			throw new Exception("Failed to connect to database.");
		}
	}
	
	private static function connect() {
		self::$conn = mysql_connect(database::$conf['host'], database::$conf['user'], database::$conf['password']);
		return mysql_select_db(database::$conf['name']);
	}
	
	private static function query($query) {
		return mysql_query($query);
	}
	
	public static function get_row($result) {
		if($result == false) {
			return false;
		} else {
			return mysql_fetch_array($result);
		}
	}
	
	public static function escape($str) {
		return mysql_real_escape_string($str);
	}
	
	public function insert_id() {
		return mysql_insert_id();
	}
	
	public static function close() {
		/* Close connection */
		return mysql_close(database::$conn);
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
	
	private function doQuery($query, array $arg) {
		/* Query wrapper to be sure everything is escaped. All SQL must go through here! */
		foreach($arg as $key => $val) {
			$arg[$key] = database::retrieve_arg($val);
		}
		
		array_unshift($arg, $query);
		$query = call_user_func_array('sprintf', $arg);
		
		$res = database::query($query);
		
		/* Die on database errors */
		if(!$res) {
			$errmsg = 'Query failed:' . $query." ". mysql_error();;
			throw new Exception($errmsg);
		}
		
		return $res;
	}
	
	public static function retrieve_arg($arg) {
		/* Escape an argument for an SQL query, or return false if there was none */
		if($arg) {
			return database::escape($arg);
		}
		return false;
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
