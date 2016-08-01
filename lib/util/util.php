<?php
use Auth\Auth;

abstract class util {
	static protected $util_name;
	
	abstract public static function init();

	/**
	 * Called to load web interface for the utility. 
	 */
	abstract public static function admin();
	
	/**
	 * Called on all enabled extensions when maintenance is needed (typically a scheduled job)
	 */
	abstract public static function doMaintenance();
	
	protected static function verifyEnabled() {
		$list = Auth::getConfig("Util");
		if(!isset($list[self::$util_name])) {
			throw new Exception("Utility is not enabled in site configuration.");
		}
	}
}