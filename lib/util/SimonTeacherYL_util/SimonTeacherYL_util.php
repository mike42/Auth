<?php
require_once(dirname(__FILE__) . "/../util.php");

class SimonTeacherYL_util extends util {
	private static $config;
	
	/**
	 * Initialise utility
	 */
	function init() {
		self::$util_name = "SimonTeacherYL";
		self::verifyEnabled();
		self::$config = Auth::getConfig(self::$util_name);
		
		Auth::loadClass("UserGroup_api");
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
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		return $data;
	}

	private static function update($apply = false) {
		throw new Exception("Unimplemented");
	}
}
