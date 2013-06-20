<?php
require_once(dirname(__FILE__) . "/../util.php");

class Cleanup_util extends util {
	private static $config;

	/**
	 * Initialise utility
	 */
	function init() {
		self::$util_name = "Cleanup";
		self::verifyEnabled();

		Auth::loadClass("Ou_api");
	}

	/**
	 * Load data for web interface
	 */
	function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		$data['service-enabled'] = Service_model::list_by_service_enabled('1');
		$data['service-disabled'] = Service_model::list_by_service_enabled('0');
		return $data;
	}

}