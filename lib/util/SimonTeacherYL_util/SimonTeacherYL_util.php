<?php
require_once(dirname(__FILE__) . "/../util.php");
require_once(dirname(__FILE__) . "/../../misc/Provisioning_Email.php");

class SimonTeacherYL_util extends util {
	/**
	 * Most recent 4 semesters
	 */
	const QUERY_SEMESTER = "SELECT TOP 4 FileSeq, FileYear, FileSemester From FileSemesters ORDER BY FileYear DESC, FileSemester DESC;";
	
	/**
	 * Student email addresses for each class
	 */
	const QUERY_STUCLASS = "SELECT Community.UID,EmailAddress, ClassCode FROM StudentClasses
			JOIN Community ON Community.UID = StudentClasses.UID
			WHERE FileSeq= %d;";
	
	/**
	 * All classes, and which subject they are under
	 */
	const QUERY_SUBCLASS = "SELECT SubjectCode, ClassCode FROM SubjectClasses
			WHERE FileSeq = %d
			ORDER BY SubjectCode, ClassCode;";
	
	/**
	 * Teacher subjects with year levels
	 */
	const QUERY_TEACHSUB = "SELECT Community.UID, Community.EmailAddress, Subjects.SubjectCode, SubjectClassStaff.ClassCode, Subjects.SubjectDescription, Subjects.NormalYearLevel
			FROM SubjectClassStaff
			JOIN Community ON Community.UID = SubjectClassStaff.UID
			JOIN SubjectClasses ON SubjectClasses.ClassCode = SubjectClassStaff.ClassCode AND SubjectClasses.FileSeq = SubjectClassStaff.FileSeq
			JOIN FileSemesters ON SubjectClassStaff.FileSeq = FileSemesters.FileSeq
			JOIN Subjects ON Subjects.SubjectCode = SubjectClasses.SubjectCode AND Subjects.FileYear = FileSemesters.FileYear
			WHERE SubjectClassStaff.FileSeq = %d
			ORDER BY cast(Subjects.NormalYearLevel as int), Subjects.SubjectCode, SubjectClassStaff.ClassCode;";
	
	private static $config;
	private static $dbh;
	
	/**
	 * Initialise utility
	 */
	public static function init() {
		self::$util_name = "SimonTeacherYL";
		self::verifyEnabled();
		self::$config = Auth::getConfig(self::$util_name);
		try {
			self::$dbh = new PDO('odbc:simon', self::$config['user'], self::$config['pass']);
			self::$dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(Exception $e) {
			throw new Exception("Database connection to SIMON failed. Please check your settings.");
		}

		Auth::loadClass("AccountOwner_api");
		Auth::loadClass("Account_model");
		Auth::loadClass("UserGroup_model");
	}
	
	/**
	 * Load data for web interface
	 */
	public static function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		$data['semester'] = self::getSemesters();
		try {
			if(isset($_POST['action']) && isset($_POST['semester'])) {
				$semester = (int)$_POST['semester'];
				$limit = isset($_POST['limit']) ? 100 : -1;
				if(!isset($data['semester'][$semester])) {
					throw new Exception("Please select a semester from the drop-down list");
				}
				switch($_POST['action']) {
					case 'check':
						self::update(false, $semester);
						break;
					case 'update':
						self::update(true, $semester, $limit);
						break;
				}
	
			}
			
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		return $data;
	}

	public static function getSemesters() {
		$sth = self::$dbh -> prepare(self::QUERY_SEMESTER);
		$sth -> execute();
		$s = $sth -> fetchAll(PDO::FETCH_ASSOC);
		$ret = array();
		foreach($s as $t) {
			$ret[$t['FileSeq']] = $t;
		}
		return $ret;
	}

	public static function doMaintenance() {
		throw new Exception("No tasks to be done. Please use the web interface.");
	}

	private static function update($apply = false, $fileseq, $limit = 100) {
		/* Get config */
		if(!$ou = Ou_model::get_by_ou_name(self::$config['group_ou_name'])) {
			throw new Exception("OU '".self::$config['group_ou_name']."' not found!");
		}
		if(!$domain_staff = ListDomain_model::get(self::$config['domain_staff'])) {
			throw new Exception("Staff domain not found. Check config.");
		}
		if(!$domain_student = ListDomain_model::get(self::$config['domain_student'])) {
			throw new Exception("Student domain not found. Check config.");
		}
		if(!$service = Service_model::get(self::$config['service_id'])) {
			throw new Exception("Email service not found. Check config.");
		}
		
		/* Teachers to groups */
		$sth = self::$dbh -> prepare(sprintf(self::QUERY_TEACHSUB, (int)$fileseq));
		$sth -> execute();
		$ts = $sth -> fetchAll(PDO::FETCH_ASSOC);
		$group = array();
		$member = array();
		$sub = array();
		foreach($ts as $t) {
			/* Year level */
			$yl = $t['NormalYearLevel'];
			if(is_numeric($yl) && $yl != "") {
				$group_cn = "year".$yl."teachers";
				if(!isset($member[$group_cn]) && !($ug = UserGroup_model::get_by_group_cn($group_cn))) {
					$ug = new UserGroup_model();
					$ug -> group_cn = $group_cn;
					$ug -> group_name = "Year $yl Teachers";
					$ug -> group_domain = $domain_staff -> domain_id;
					$group[] = $ug;
				}
				$member[$group_cn][$t['EmailAddress']] = true;
			}
			
			$subj = $t['SubjectCode'];
			$class =  $t['ClassCode'];

			/* Subject teachers */
			$group_cn = "teachers".$subj;
			if(!isset($member[$group_cn]) && !($ug = UserGroup_model::get_by_group_cn($group_cn))) {
				$ug = new UserGroup_model();
				$ug -> group_cn = $group_cn;
				$ug -> group_name = "Teachers $subj: " . $t['SubjectDescription'];
				$ug -> group_domain = $domain_staff -> domain_id;
				$group[] = $ug;
			}
			$member[$group_cn][$t['EmailAddress']] = true;
			
			/* Subject group */
			$group_cn = "subject".$subj;
			if(!isset($member[$group_cn]) && !($ug = UserGroup_model::get_by_group_cn($group_cn))) {
				$ug = new UserGroup_model();
				$ug -> group_cn = $group_cn;
				$ug -> group_name = "Subject $subj: ".$t['SubjectDescription'];
				$ug -> group_domain = $domain_student -> domain_id;
				$group[] = $ug;
			}
			$member[$group_cn] = array();
			$sub[$group_cn]["teachers".$subj] = true;

			/* Class group */
			$group_cn = "class".$class;
			if(!isset($member[$group_cn]) && !($ug = UserGroup_model::get_by_group_cn($group_cn))) {
				$ug = new UserGroup_model();
				$ug -> group_cn = $group_cn;
				$ug -> group_name = "Class $class: ".$t['SubjectDescription'];
				$ug -> group_domain = $domain_student -> domain_id;
				$group[] = $ug;
			}
			$member[$group_cn][$t['EmailAddress']] = true;
		}

		/* Students in classes */
		$sth = self::$dbh -> prepare(sprintf(self::QUERY_STUCLASS, (int)$fileseq));
		$sth -> execute();
		$sc = $sth -> fetchAll(PDO::FETCH_ASSOC);
		foreach($sc as $s) {
			$class =  $s['ClassCode'];
			$group_cn = "class".$class;
			if(isset($member[$group_cn])) {
				$member[$group_cn][$s['EmailAddress']] = true;
			}
		}

		/* Classes under subject */
		$sth = self::$dbh -> prepare(sprintf(self::QUERY_SUBCLASS, (int)$fileseq));
		$sth -> execute();
		$sg = $sth -> fetchAll(PDO::FETCH_ASSOC);
		foreach($sg as $s) {
			$class =  $s['ClassCode'];
			$subj = $s['SubjectCode'];
			$class_cn = "class".$class;
			$subj_cn = "subject".$subj;
			
			if(isset($member[$subj_cn])) {
				$sub[$subj_cn][$class_cn] = true;
			}
		}
		
		/* Create groups */
		$count = 0;
		if(count($group) != 0) {
			if(!$apply) {
				throw new Exception("Need to create ".count($group) . " groups.");
			} else {
				foreach($group as $g) {
					UserGroup_api::create($g -> group_cn, $g -> group_name, $ou -> ou_id, $g -> group_domain);
					$count++;
					if($count >= $limit && $limit != -1) {
						throw new Exception("Stopped after $limit operations. Click Update to continue");
					}
				}
				throw new Exception("All groups created");
			}
		}
		
		$count_add  = 0;
		$count_rm = 0;
		$notfound = array();
		$todo = array();
		/* Compute new things to add */
		foreach($member as $group_cn => $memberList) {
			if($ug = UserGroup_model::get_by_group_cn($group_cn)) {
				$ug -> populate_list_OwnerUserGroup();
				$add = array();
				$rm = array();
				foreach($ug -> list_OwnerUserGroup as $oug) {
					$rm[$oug -> owner_id] = true;
				}
				foreach($memberList as $m => $true) {
					$me = new Provisioning_Email($m);
					$account_login = $me -> local;
					$account_domain = self::getDomainId($me -> domain);
					if($account = Account_model::get_by_account_login($account_login, $service -> service_id, $account_domain)) {
						if(isset($rm[$account -> owner_id])) {
							unset($rm[$account -> owner_id]);
						} else {
							$add[$account -> owner_id] = true;
						}
					} else {
						$notFound[$m] = true;
					}
				}
				$todo[$ug -> group_id] = array("add" => $add, "rm" => $rm);
				$count_add += count($add);
				$count_rm += count($rm);
			} else {
				throw new Exception("Group '".  $group_cn . "' not found, but should exist!");
			}
		}
		
		if(!$apply) {
			throw new Exception("$count_add members to add, $count_rm members to remove, ".count($notFound) . " unrecognised.");
		} else {
			foreach($todo as $group_id => $item) {
				foreach($item['add'] as $owner_id => $true) {
					// TODO add users to group
					
					$count++;
					if($count >= $limit && $limit != -1) {
						throw new Exception("Stopped after $limit operations. Click Update to continue");
					}
				}
				
				foreach($item['rm'] as $owner_id => $true) {
					// TODO remove users from group
						
					$count++;
					if($count >= $limit && $limit != -1) {
						throw new Exception("Stopped after $limit operations. Click Update to continue");
					}
				}
			}
			
			
		}
		
		/* Sub-groups */
		$count_add  = 0;
		$count_rm = 0;
		foreach($sub as $group_cn => $memberList) {
			if($ug = UserGroup_model::get_by_group_cn($group_cn)) {
				$children = UserGroup_api::list_children($ug -> group_id);
				$add = array();
				$rm = array();
				
				foreach($children as $sug) {
					$rm[$sug -> group_id] = true;
				}
				
				foreach($memberList as $sug_cn => $true) {
					if($sug = UserGroup_model::get_by_group_cn($sug_cn)) {
						if(isset($rm[$sug -> group_id])) {
							unset($rm[$sug -> group_id]);
						} else {
							$add[$sug -> group_id] = true;
						}
					}
				}
				$todo[$ug -> group_id] = array("add" => $add, "rm" => $rm);
				$count_add += count($add);
				$count_rm += count($rm);
			}
		}
		
		if(!$apply) {
			throw new Exception("$count_add sub-groups to add, $count_rm sub-groups to remove.");
		} else {
			foreach($todo as $group_id => $item) {
				foreach($item['add'] as $subgroup_id => $true) {
					// TODO add subgroups
					
					$count++;
					if($count >= $limit && $limit != -1) {
						throw new Exception("Stopped after $limit operations. Click Update to continue");
					}
				}
				
				foreach($item['rm'] as $subgroup_id => $true) {
					// TODO remove subgroups
						
					$count++;
					if($count >= $limit && $limit != -1) {
						throw new Exception("Stopped after $limit operations. Click Update to continue");
					}
				}
			}
			throw new Exception("Everything is done!");
		}
		
		throw new Exception("Nothing to do!");
	}
	
	private static function getDomainId($domain) {
		return array_search($domain, self::$config['domain']);
	}
}