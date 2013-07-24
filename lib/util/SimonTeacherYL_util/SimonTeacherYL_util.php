<?php
require_once(dirname(__FILE__) . "/../util.php");

class SimonTeacherYL_util extends util {
	private static $config;
	
	/**
	 * Initialise utility
	 */
	function init() {
		self::$util_name = "SimonTeacherYL";
		self::verifyEnabled();
		self::$config = Auth::getConfig(self::$util_name);
		
		Auth::loadClass("AccountOwner_api");
		Auth::loadClass("Account_model");
		Auth::loadClass("UserGroup_model");
	}

	/**
	 * Load data for web interface
	 */
	function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		try{
			if(isset($_POST['action'])) {
				/* Allow semester 1 or 2 to be selected */
				if(!isset($_POST['semester'])) {
					throw new Exception("Please select a semester from the dropdown box");
				}
				$data['semester'] = (int)$_POST['semester'];
				if($data['semester'] < 1 || $data['semester'] > 2) {
					throw new Exception("Please select a semester from the dropdown box");
				}
				/* Choose action */
				switch($_POST['action']) {
					case "check":
						$data['run'] = self::update(false, $data['semester']);
						break;
					case "update":
						$data['run'] = self::update(true, $data['semester']);
						break;
					case "notifySelect":
						$data['notify'] = self::notify($data['semester']);
						$data['template'] = "notify-select";
						break;
					case "notify":
						$address = array();
						foreach($_POST as $addr => $on) {
							if(!(strpos($addr, "@") === false)) {
								$address[$addr] = true;
							}
						}

						$data['notify'] = self::notify($data['semester'], $address);
						break;
				}
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		return $data;
	}

	private static function update($apply = false, $semester = 1) {
		/* Put users in correct groups, or do a test run where $apply = false */
		$data = self::loadTeacherYL($semester);
		
		$groupcheck = array('pass' => array(), 'fail' => array());
		$groupmembers = array();
		
		foreach($data['yl'] as $level => $list) {
			/* Verify that all of the year-level groups exist */
			$group_cn = mkgrpname($level);
			if(!isset($groupcheck['pass'][$group_cn]) && !isset($groupcheck['fail'][$group_cn])) {
				if($ug = UserGroup_model::get_by_group_cn($group_cn)) {
					$groupcheck['pass'][$group_cn] = $ug -> group_id;
					$ug -> populate_list_OwnerUserGroup();
					$groupmembers[$group_cn] = array();
					foreach($ug -> list_OwnerUserGroup as $oug) {
						$groupmembers[$group_cn][$oug -> owner_id] = true;
					}
				} else {
					$groupcheck['fail'][$group_cn] = true;
				}
			}
			
			if(isset($groupcheck['pass'][$group_cn])) { // Only interested in extant groups
				$group_id = $groupcheck['pass'][$group_cn];
				/* Compare lists */
				foreach($list as $account_login => $details) {
					if(isset($data['unamecheck']['pass'][$account_login])) { // Only bother with verified logins
						$owner_id = $data['unamecheck']['pass'][$account_login];
						if(isset($groupmembers[$group_cn][$owner_id])) {
							unset($groupmembers[$group_cn][$owner_id]); // User already in group
						} else {
							// Need to add $owner_id to group_id
							if($apply) {
								try {
									AccountOwner_api::addtogroup($owner_id, $group_id);
								} catch(Exception $e) {
									// Ignore. You only get here if the user is already in a sub-group, which is not a problem
								}
							}
						}
					}
				}
				
				foreach($groupmembers[$group_cn] as $owner_id => $true) {
					// Need to remove from group
					if($apply) {
						try {
							AccountOwner_api::rmfromgroup($owner_id, $group_id);
						} catch(Exception $e) {
							// Ignore. User probably removed from group while the script was running?
						}
					}
				}
			}
		}

		ksort($data['yl']); // So that these come out in numeric order
		$data['groupcheck'] = $groupcheck;
		return $data;
	}
	
	static function loadTeacherYL($semester) {
		$service = Service_model::get(self::$config['check']);
		
		/* Query for loading data from external database */
		$query = "SELECT \n" .
				"SubjectClasses.ClassCode, Subjects.Semester1Code, Subjects.Semester2Code, \n" .
				"Subjects.SubjectCode, Subjects.SubjectDescription, Subjects.NormalYearLevel, \n" .
				"Community.Preferred, Community.Surname, Community.EmailAddress \n" .
				"FROM dbo.SubjectClassStaff \n" .
				"JOIN dbo.SubjectClasses ON SubjectClassStaff.ClassCode = SubjectClasses.ClassCode \n" .
				"JOIN CISNet3.dbo.Subjects ON Subjects.SubjectCode = SubjectClasses.SubjectCode \n" .
				"JOIN CISNet3.dbo.Community ON community.UID = SubjectClassStaff.UID \n" .
				"ORDER BY SubjectClasses.ClassCode, Subjects.SubjectDescription; \n" .
				"\go -f \n" .
				"quit \n" .
				"EOF;";
		
		/* Run command and get ouput */
		$command = sprintf("sqsh " .
				"-S %s \\\n" .
				"-U %s \\\n" .
				"-D %s \\\n" .
				"-P %s \\\n" .
				"-mbcp << EOF \n" .
				"%s\n" .
				"EOF", escapeshellarg(self::$config['host']), escapeshellarg(self::$config['user']), escapeshellarg(self::$config['name']), escapeshellarg(self::$config['pass']), $query);
		$lines = array();
		$people = array();
		$yl = array();
		$ylMember = array();
				
		exec($command, $lines, $ret);
		if($ret != 0) {
			throw new Exception("Command failed. Verify that everything is configured correctly: sqsh returned $ret.");
		}
		
		/* Calculate some key details */
		$unamecheck = array('pass' => array(), 'fail' => array()); // Verifying that user accounts actually exist
		
		foreach($lines as $line) {
			$part = explode("|", $line);
		
			$member = new YLGroupMember();
			$member -> ClassCode = trim($part[0]);
			$member -> Semester1Code = trim($part[1]);
			$member -> Semester2Code = trim($part[2]);
			$member -> SubjectCode = trim($part[3]);
			$member -> SubjectDescription = trim($part[4]);
			$member -> NormalYearLevel = trim($part[5]);
			if(is_numeric($member -> NormalYearLevel) && (int)$member -> NormalYearLevel >= self::$config['yl_min'] && $member -> NormalYearLevel <= self::$config['yl_max']) {
				$member -> NormalYearLevel = (int)$member -> NormalYearLevel;
			} else {
				$member -> NormalYearLevel = false;
			}
			$member -> Preferred = trim($part[6]);
			$member -> Surname  = trim($part[7]);
			$member -> EmailAddress = $part[8];
			$member -> EmailAlias = strpos($part[8], '@') === false? $part[8] : substr($part[8], 0, strpos($part[8], '@')); // Ignore email domain (service-independent, may not be checking with a service that does an email backend)
			$account_login = $member -> EmailAlias;
			if(!isset($unamecheck['pass'][$account_login]) && !isset($unamecheck['fail'][$account_login])) {
				/* Lookup user in DB */
				$service_id = $service -> service_id;
				$account_domain = self::$config['domain'];
				if($account = Account_model::get_by_account_login($account_login, $service_id, $account_domain)) {
					$unamecheck['pass'][$account_login] = $account -> owner_id;
				} else {
					$unamecheck['fail'][$account_login] = true;
				}
			}
			$member -> Semester = $member -> calcSemester();
		
			if($member -> Semester == $semester && !isset($unamecheck['fail'][$account_login])) { // Only use valid usernames within the correct semester
				if(!isset($people[$member -> EmailAddress])) {
					$people[$member -> EmailAddress] = array();
				}
				$people[$member -> EmailAddress][] = $member;
		
				if($member -> NormalYearLevel) {
					if(!isset($yl[$member -> NormalYearLevel])) {
						$yl[$member -> NormalYearLevel] = array();
					}
					if(!isset($yl[$member -> NormalYearLevel][$member -> EmailAlias])) {
						$yl[$member -> NormalYearLevel][$member -> EmailAlias] = array();
					}
					$yl[$member -> NormalYearLevel][$member -> EmailAlias][] = $member;
					$ylMember[$member -> EmailAlias][$member -> NormalYearLevel] = true;
				}
			}
		}
		
		return array('yl' => $yl, 'ylMember' => $ylMember, 'unamecheck' => $unamecheck, 'people' => $people);
	}

	private static function notify($semester = 1, $send = array()) {
		/* Put users in correct groups, or do a test run where $apply = false */
		$data = self::loadTeacherYL($semester);
		
		$fn = dirname(__FILE__) . "/../../../site/SimonTeacherYL-notify.inc";
		if(!file_exists($fn)) {
			throw new Exception("Template file $fn needs to be created");
		}
		
		$ylMember = $data['ylMember'];
		foreach($data['people'] as $email => $person) {
			if(isset($send[str_replace(".", "_", $email)])) {
				$message = "";
				include($fn); // Include file as template
				self::sendNotifyEmail($email, "Your semester $semester email groups", $message);
			}
		}
		return $data;
	}
	
	private static function sendNotifyEmail($address, $subject, $message) {
		$to = $address;
		$from = self::$config['from'];
		$headers = "From: $from\r\nContent-Type: text/html; charset=UTF-8";
		mail($to, $subject, $message, $headers);
	}
}

function mkgrpname($yl) {
	/* Return group_cn for the given year level */
	return "year".$yl."teachers";
}

class YLGroupMember {
	public $ClassCode;
	public $Semester1Code;
	public $Semester2Code;
	public $SubjectCode;
	public $SubjectDescription;
	public $NormalYearLevel;
	public $Preferred;
	public $Surname;
	public $EmailAddress;
	public $EmailAlias;
	public $Semester;

	public function calcSemester() {
		/* Figure out which semester this class is in, and return it (0 for unknown) */

		if(substr($this -> ClassCode, 0, strlen($this -> Semester1Code)) == $this -> Semester1Code) {
			return 1;
		} else if(substr($this -> ClassCode, 0, strlen($this -> Semester2Code)) == $this -> Semester2Code) {
			return 2;
		} else {
			return 0;
		}
	}
}