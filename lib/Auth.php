<?php 
/**
 * This is the main class for Auth. It is simply responsible for loading classes and configuration.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */

namespace Auth;

use \Exception;

class Auth {
	/**
	 * Load a class by name
	 * 
	 * @param string $className The name of the class to load.
	 */
	static public function loadClass($className) {
		$sp = explode("_", $className);

		if(count($sp) == 1) {
			/* If there are no underscores, it should be in misc */
			$sp[0] = self::alphanumeric($sp[0]);
			$fn = dirname(__FILE__)."/misc/".$sp[0].".php";
			$init = "Auth\\misc\\" . $sp[0];
		} else {
			/* Otherwise look in the folder suggested by the name */
			$folder = self::alphanumeric(array_pop($sp));
			$classfile = Auth::alphanumeric($className);
			if($folder == "util") {
				/* Utilities are self-contained in their own folder */
				$fn = dirname(__FILE__)."/$folder/$classfile/$classfile.php";
				$init = "Auth\\$folder\\$classfile\\$classfile";
			} else {
				$fn = dirname(__FILE__)."/$folder/$classfile.php";
				$init = "Auth\\$folder\\$classfile";
			}
		}

		if(!class_exists($init, false)) {
			self::loadClassFromFile($fn, $className, $init);
		}
	}
	
	/**
	 * Load a class given its filename, and call FooClass::init()
	 * 
	 * @param string $fn		Filename where we expect to find this class
	 * @param string $className	Name of the class being loaded
	 * @throws Exception
	 */
	static public function loadClassFromFile($fn, $className, $init) {
		if(!file_exists($fn)) {
			throw new Exception("The class '$className' could not be found at $fn.");
		}

		require_once($fn);

		if(is_callable($init . "::init")) {
			call_user_func($init . "::init");
		}
	}
	
	/**
	 * @param unknown_type $classname
	 * @throws Exception
	 * @return unknown
	 */
	static public function getConfig($classname) {
		include(dirname(__FILE__) . "/../site/config.php");
		$classnameParts = explode("\\", $classname);
		$section = array_pop($classnameParts);
		if(!isset($config[$section])) {
			throw new Exception("No configuration found for '$section'");
		}
		return $config[$section];
	}
	
	/**
	 * Clear anything other than alphanumeric characters from a string (to prevent arbitrary inclusion)
	 * 
	 * @param string $inp	An input string to be sanitised.
	 * @return string		The input string containing alphanumeric characters only
	 */
	static public function alphanumeric($inp) {
		return preg_replace("#[^-a-zA-Z0-9]+#", "_", $inp);
	}
	
	/**
	 * This function cleans up a string for use in a group/user name, to enforce simple names
	 * 
	 * @param string $inp
	 */
	static public function normaliseName($inp) {
		return strtolower(preg_replace("#[^-a-zA-Z0-9.'_]+#", "", trim($inp)));
	}
	
	/**
	 * Return true if debugging is enabled, false if not. Some functions log less
	 * data and remove dangerous features when debugging is off (a good idea for production installs)
	 */
	static public function isDebug() {
		$conf = Auth::getConfig("login");
		return isset($conf['debug']) && $conf['debug'] == true;
	}
}