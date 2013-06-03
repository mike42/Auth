<?php
require_once(dirname(__FILE__) . "/../util.php");

class SasStudent_util extends util {
	/**
	 * Initialise utility
	 */
	function init() {
		self::$util_name = "SasStudent";
	}
	
	/**
	 * Load data for web interface
	 */
	function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		
		// Nothing implemented yet
		
		return $data;
	}
}