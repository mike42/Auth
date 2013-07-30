<?php
require_once(dirname(__FILE__) . "/../util.php");

/**
 * @author mike
 *
 */
class Example_util extends util {
	private static $config;
	
	/**
	 * Initialise utility
	 */
	public static function init() {
		self::$util_name = "Example";
		self::verifyEnabled();
	
		// Use Auth::loadClass to load dependencies
	}
	
	/**
	 * Load data for web interface
	 */
	public static function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		
		// Find data to display
		if(isset($_POST['helloworld'])) {
			$data['message'] = "Hello World";
		}
		
		return $data;
	}
	
	/**
	 * Do any maintenance tasks
	 */
	public static function doMaintenance() {
		
		// Do tasks here
		throw new Exception("Unimplemented");
	}
}