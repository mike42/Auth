<?php
abstract class util {
	static protected $util_name;
	
	/**
	 * Called to load web interface for the utility. 
	 */
	abstract function admin();
	
	protected function verifyEnabled() {
		$list = Auth::getConfig("Util");
		if(!isset($list[self::$util_name])) {
			throw new Exception("Utility is not enabled in site configuration.");
		}
	}
}