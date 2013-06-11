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
		/* Run command and get ouput */
		$command = sprintf("sqsh " .
				"-S %s \\\n" .
				"-U %s \\\n" .
				"-D %s \\\n" .
				"-P %s \\\n" .
				"-mbcp << EOF \n" .
				"select * from %s;\n" .
				"\go -f\n" .
				"quit\n" .
				"EOF", escapeshellarg(self::$config['host']), escapeshellarg(self::$config['user']), escapeshellarg(self::$config['name']), escapeshellarg(self::$config['pass']), mysql_real_escape_string(self::$config['view']));
		$lines = array();
		$ret = exec($command, $lines);
		if($ret != 0) {
			throw new Exception("Command failed. Verify that everything is configured correctly");
		}
		
		$prefix = self::$config['prefix'];
		$reject = $hr_suggest = $rename = array();
		
		foreach($lines as $line) {
			$var = explode("|", $line);
			if(count($var) == 8) {
				$sas_stuno = $var[0];
				$sas_firstname = trim($var[1]);
				$sas_surname = trim($var[2]);
				$sas_preferred_name = trim($var[3]);
				$sas_yl = trim($var[4]);
				$sas_hr = trim($var[5]);
				$exists[$sas_stuno] = true;
				
				/* Validate */
				if(!is_numeric($sas_stuno)) {
					$reject[] = array('var' => $var, 'reason' => 'Student number is not numeric');
					continue;
				} else if(!is_numeric($sas_yl)) {
					$reject[] = array('var' => $var, 'reason' => 'Year level is not numeric');
					continue;
				}
				
				$group_cn = $prefix . strtolower($sas_hr);
				$group_name = strtoupper(substr($prefix, 0, 1)) . substr($prefix, 1, strlen($prefix) - 1) . ' ' . strtoupper($sas_hr);
				if(!$group = UserGroup_model::get_by_group_cn($group_cn)) {
					if($group_cn != "") {
						$hr_suggest[$group_cn] = true;
					}
					$group_cn = $prefix."unknown";
				} else {
					if($group -> group_name != $group_name) {
						$rename[$group_cn] = $group_name;
						
						if($apply) {
							UserGroup_api::rename($group -> group_id, $group_name, $group -> group_cn);
						}
					}
				}
			}
		}
			
		
		print_r($reject);
		print_r($hr_suggest);
		print_r($rename);
		return $lines;
	}
}