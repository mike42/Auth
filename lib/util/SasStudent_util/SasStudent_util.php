<?php
require_once(dirname(__FILE__) . "/../util.php");

class SasStudent_util extends util {
	private static $config;
	
	/**
	 * Initialise utility
	 */
	function init() {
		self::$util_name = "SasStudent";
		self::verifyEnabled();
		self::$config = Auth::getConfig(self::$util_name);
	}
	
	/**
	 * Load data for web interface
	 */
	function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		try{
			if(isset($_POST['action'])) {
				switch($_POST['action']) {
					case "check":
						$lines = self::update(false);
						break;
					case "update":
						$lines = self::update(true);
						break;
				}
			}
			if(isset($lines)) {
				$data['result'] = $lines;
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		// Nothing implemented yet
		
		return $data;
	}
	
	/**
	 * Look for areas in which SAS & local accounts do not add up, and fix them
	 * 
	 * @param boolean $apply
	 */
	private static function update($apply = false) {
		//print_r(self::$config);
		throw new Exception("Unimplemented");
		$command = sprintf("sqsh " .
				"-S %s \\\n" .
				"-U %s \\\n" .
				"-D %s \\\n" .
				"-P %s \\\n" .
				"-mbcp << EOF \n" .
				"select * from dbo.sjcauthStudentView;\n" .
				"\go -f\n" .
				"quit\n" .
				"EOF", $host, $user, $name, $pass);
		return $lines;
	}
}