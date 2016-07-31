<?php
use Auth\web\Web;

require_once(dirname(__FILE__) . "/../util.php");

class SasStudent_util extends util {
	private static $config;
	
	/**
	 * Initialise utility
	 */
	public static function init() {
		self::$util_name = "SasStudent";
		self::verifyEnabled();
		self::$config = Auth::getConfig(self::$util_name);
		
		Auth::loadClass("UserGroup_api");
	}
	
	/**
	 * Load data for web interface
	 */
	public static function admin() {
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
		
		return $data;
	}
	
	public static function doMaintenance() {
		$lines = self::update(true);
	}
	
	/**
	 * Look for areas in which SAS & local accounts do not add up, and fix them
	 * 
	 * @param boolean $apply
	 */
	private static function update($apply = false) {
		$service = Service_model::get(self::$config['check']);
		
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
		exec($command, $lines, $ret);
		if($ret != 0) {
			throw new Exception("Command failed. Verify that everything is configured correctly: sqsh returned $ret");
		}
		
		$prefix = self::$config['prefix'];
		$reject = $hr_suggest = $create = $move = $rename = $delete = array();
		$exists = array();
		$grpExists = array();
		$grpAdd = $grpRemove = 0;
		
		foreach($lines as $line) {
			$var = explode("|", $line);
			if(count($var) == 8) {
				$sas_stuno = $var[0];
				$sas_firstname = trim($var[1]);
				$sas_surname = trim($var[2]);
				$sas_preferred_name = trim($var[3]);
				if($sas_preferred_name == "") {
					$sas_preferred_name = $sas_firstname;
				}
				$sas_yl = trim($var[4]);
				$sas_hr = trim($var[5]);
				$exists[$sas_stuno] = true;
				
				/* Validate */
				if(!is_numeric($sas_stuno)) {
					$reject[] = array('var' => $var, 'reason' => 'Student number is not numeric');
					continue;
				} else if(!is_numeric($sas_yl) || (int)$sas_yl < 7 || (int)$sas_yl > 12) {
					$reject[] = array('var' => $var, 'reason' => 'Year level is not numeric, or is too high or low.');
					continue;
				}
				
				/* Make groups correctly */
				$group_cn = $prefix . strtolower($sas_hr);
				if(isset($group_exists[$group_cn])) {
					$group = $group_exists[$group_cn];
				} else {
					$group_name = strtoupper(substr($prefix, 0, 1)) . substr($prefix, 1, strlen($prefix) - 1) . ' ' . strtoupper($sas_hr);
					if(!$group = UserGroup_model::get_by_group_cn($group_cn)) {
						if($group_cn != "") {
							$hr_suggest[$group_cn] = true;
						}
						$group_cn = $prefix."unknown";
						$group = UserGroup_model::get_by_group_cn($group_cn); // Get again from new name (may or may not exist but at least we tried)
						if($group) {
							$groupExists[$group -> group_id] = $group;
						}
					} else {
						if($group -> group_name != $group_name) {
							$rename[$group_cn] = $group_name;
							
							if($apply) {
								UserGroup_api::rename($group -> group_id, $group_name, $group -> group_cn);
							}
						}

						$groupExists[$group -> group_id] = $group;
					}
				}

				$ou_name = "y" . ((int)date("Y") + (12 - (int)$sas_yl)); // Name of OU
				if(!$ou = Ou_model::get_by_ou_name($ou_name)) {
					$parent_ou_name = self::$config['ou'];
					if(!$parent = Ou_model::get_by_ou_name($parent_ou_name)) {
						throw new Exception("Can't put student in new ou '$ou_name' because '$parent_ou_name' was not found. Edit config or create '$parent_ou_name'.");
					}
						
					if($apply) {
						$ou = Ou_api::create($ou_name, $parent -> ou_id);
					}
				}
				
				if($login = Account_model::get_by_account_login($sas_stuno, $service -> service_id, $service -> service_domain)) {
					if($login -> AccountOwner -> Ou -> ou_name != $ou_name) {
						$move[] = array('var' => $var);						
						if($apply) {
							AccountOwner_api::move($login -> owner_id, $ou -> ou_id);
						}
					}
					
					/* Remove from incorrect groups */
					$ao = $login -> AccountOwner;
					$ao -> populate_list_OwnerUserGroup();
					$foundCorrect = false;
					foreach($ao -> list_OwnerUserGroup as $oug) {
						if(strlen($oug -> UserGroup -> group_cn) >= strlen($prefix) && substr($oug -> UserGroup -> group_cn, 0, strlen($prefix)) == $prefix) {
							if($group && $oug -> UserGroup -> group_cn == $group -> group_cn) {
								$foundCorrect = true;
							} else {
								if($apply) {
									AccountOwner_api::rmfromgroup($oug -> owner_id, $oug -> group_id);
								}
								$grpRemove++;
							}
						}
					}
					
					if(!$foundCorrect && $group) {
						if($apply) {
							AccountOwner_api::addtogroup($ao -> owner_id, $group -> group_id);
						}
						$grpAdd++;
					}
				} else {
					$create[] = array('var' => $var);
					if($apply) {
						$ao = AccountOwner_api::create($ou -> ou_id, $sas_preferred_name, $sas_surname, $sas_stuno, 'students', self::$config['create']);
						
						if($group) {
							AccountOwner_api::addtogroup($ao -> owner_id, $group -> group_id);
						}
					}
				}
			}
		}

		$accList = Account_model::list_by_service_id($service -> service_id);
		foreach($accList as $a) {
			if(is_numeric($a -> account_login) && !isset($exists[(int)$a -> account_login])) {
				$delete[] = array('num' => $a -> account_login, 'firstname' => $a -> AccountOwner -> owner_firstname, 'surname' => $a -> AccountOwner -> owner_surname);
				if($apply) {
					AccountOwner_api::delete($a -> owner_id);
				}
			}
		}
		
		return array('reject' => $reject, 'hr_suggest' => $hr_suggest, 'rename' => $rename, 'create' => $create, 'move' => $move, 'delete' => $delete, 'grpAdd' => $grpAdd, 'grpRemove' => $grpRemove);
	}
}
