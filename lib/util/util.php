<?php
abstract class util {
	static protected $util_name;
	
	/**
	 * Called to load web interface for the utility. 
	 */
	abstract function admin();
}