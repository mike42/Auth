<?php 
/**
 * This is the main class for Auth. It is simply responsible for loading classes and configuration.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class Auth {	
	/**
	 * Load a class by name
	 * 
	 * @param string $className The name of the class to load.
	 */
	static public function loadClass($className) {
		if(!class_exists($className)) {
			$sp = explode("_", $className);
			
			if(count($sp) == 1) {
				/* If there are no underscores, it should be in misc */
				$sp[0] = self::alphanumeric($sp[0]);
				$fn = dirname(__FILE__)."/misc/".$sp[0].".php";
			} else {
				/* Otherwise look in the folder suggested by the name */
				$folder = self::alphanumeric(array_pop($sp));
				$classfile = Auth::alphanumeric($className);
				if($folder == "util") {
					/* Utilities are self-contained in their own folder */
					$fn = dirname(__FILE__)."/$folder/$classfile/$classfile.php";
				} else {
					$fn = dirname(__FILE__)."/$folder/$classfile.php";
				}
			}

			self::loadClassFromFile($fn, $className);
		}
	}
	
	/**
	 * Load a class given its filename, and call FooClass::init()
	 * 
	 * @param string $fn		Filename where we expect to find this class
	 * @param string $className	Name of the class being loaded
	 * @throws Exception
	 */
	static public function loadClassFromFile($fn, $className) {
		if(!file_exists($fn)) {
			throw new Exception("The class '$className' could not be found at $fn.");
		}
		
		require_once($fn);
		if(is_callable($className . "::init")) {
			call_user_func($className . "::init");
		}
	}
	
	/**
	 * @param unknown_type $section
	 * @throws Exception
	 * @return unknown
	 */
	static public function getConfig($section) {
		include(dirname(__FILE__) . "/../site/config.php");
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

}