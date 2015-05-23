<?php
require_once(dirname(__FILE__) . "/../util.php");

/**
 * @author mike
 *
 */
class Cleanup_util extends util {
	private static $config;

	private static $debug;

	/**
	 * Initialise utility
	 */
	public static function init() {
		self::$util_name = "Cleanup";
		self::verifyEnabled();
		
		Auth::loadClass("Ou_api");
		Auth::loadClass("ActionQueue_api");
		self::$debug = Auth::isDebug();
	}

	/**
	 * Load data for web interface
	 */
	public static function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		
		if(isset($_POST['action'])) {
			try {
				$action = $_POST['action'];
				switch($action) {
					case 'recSearch':
					case 'sync':
					case 'deactivate':	
					case 'activate':
						if(!isset($_POST['service_id'])) {
							throw new Exception("Service name required");
						}
						
						if(!$service = Service_model::get($_POST['service_id'])) {
							throw new Exception("Service not found");
						}

						switch($action) {
							case 'recSearch':
								self::recSearch($service);
								break;
							case 'sync':
								self::sync($service);
								break;
							case 'deactivate':
								self::deactivate($service);
								break;
							case 'activate':
								self::activate($service);
								break;
						}
						break;
					case 'localCleanup':
						$count = self::localCleanup();
						$data['message'] = "$count users deleted.";
						break;
					case 'processAq':
						self::processAq();
						$data['message'] = "The queue has been started.";
						break;
					case 'emptyAq':
						self::emptyAq();
						$data['message'] = "The queue has been emptied.";
						break;
					case 'eraseLocal':
						if(self::$debug) {
							self::eraseLocal();
							$data['message'] = "All local entries have been erased.";
						} else {
							$data['message'] = "This feature is disabled.";
						}
						break;
					case 'dummyData':
						if(self::$debug) {
							self::dummyData();
							$data['message'] = "Dummy data has been introduced.";
						} else {
							$data['message'] = "This feature is disabled.";
						}
						break;
					default:
						throw new Exception("Unknown action: $action");
				}
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}		
		
		$data['service-enabled'] = Service_model::list_by_service_enabled('1');
		$data['service-disabled'] = Service_model::list_by_service_enabled('0');
		$data['debug'] = self::$debug;
		return $data;
	}

	public static function doMaintenance() {
		/* Local cleanup */
		$count = self::localCleanup();
		echo "\t$count users deleted\n";
		
		/* Queue must be empty */
		ActionQueue_api::runUntilEmpty(false);
		
		Auth::loadClass("Service_model");
		$services = Service_model::list_by_service_enabled('1');
		foreach($services as $service) {
			/* Search & sync every service */
			self::recSearch($service);
			ActionQueue_api::runUntilEmpty(false);

			self::sync($service);
			ActionQueue_api::runUntilEmpty(false);
		}
	}
	
	/**
	 * Empty the action queue
	 */
	static function emptyAq() {
		ActionQueue_api::flush();
		return true;
	}
	
	/**
	 * Process the action queue
	 */
	static function processAq() {
		ActionQueue_api::start(true);
		return true;
	}
	
	/**
	 * Clean up local accounts
	 */
	static function localCleanup() {
		$count = 0;
		if($ou = Ou_model::get_by_ou_name("root")) {
			$count = self::cleanup($ou);
		}
		return $count;
	}
	
	static private function cleanup(Ou_model $ou) {
		$count = 0;
		
		$accountOwners = AccountOwner_model::list_by_ou_id($ou -> ou_id);
		foreach($accountOwners as $ao) {
			$ao -> populate_list_Account();
			if(count($ao -> list_Account) == 0) {
				/* Needs to be removed */
				$count += 1;
				AccountOwner_api::delete($ao -> owner_id);
			}
		}
		
		$ouList = Ou_model::list_by_ou_parent_id($ou -> ou_id);
		foreach($ouList as $child) {
			$count += self::cleanup($child);
		}
		return $count;
	}
	
	/**
	 * Search a service
	 */
	static function recSearch(Service_model $service) {
		if(ActionQueue_api::count() > 0) {
			// This is not a real rule, but it will save running it twice
			throw new Exception("A search can only be added if the queue is empty!");
		}
		ActionQueue_api::submit($service -> service_id, $service -> service_domain, 'recSearch', 'root');
	}
	
	/**
	 * Sync the given service
	 */
	static function sync(Service_model $service) {
		if(ActionQueue_api::count() > 0) {
			// This is not a real rule, but it will save running it twice
			throw new Exception("A search can only be added if the queue is empty!");
		}
		ActionQueue_api::submit($service -> service_id, $service -> service_domain, 'syncOu', 'root');
	}
	
	/**
	 * De-activate the given service
	 */
	static function activate(Service_model $service) {
		$service -> service_enabled = '1';
		$service -> update();
		return true;
	}
	
	/**
	 * Activate the given service.
	 */
	static function deactivate(Service_model $service) {
		$service -> service_enabled = '0';
		$service -> update();
		return true;
	}
	
	/**
	 * Erase local data
	 */
	static function eraseLocal() {
		$stack = array(Ou_model::get_by_ou_name("root"));
		
		while(count($stack) != 0) {
			$ou = array_pop($stack);
			
			$ou -> populate_list_UserGroup();
			foreach($ou -> list_UserGroup as $ug) {
				$ug -> populate_list_OwnerUserGroup();
				foreach($ug -> list_OwnerUserGroup as $oug) {
					$oug -> delete();
				}
				
				$ug -> populate_list_SubUserGroup();
				foreach($ug -> list_SubUserGroup as $sug) {
					$sug -> delete();
				}
				$ug -> delete();
			}
			
			$ou -> populate_list_AccountOwner();
			foreach($ou -> list_AccountOwner as $owner) {
				$owner -> populate_list_Account();
				foreach($owner -> list_Account as $account) {
					$account -> delete();
				}
				
				$owner -> populate_list_OwnerUserGroup();
				foreach($owner -> list_OwnerUserGroup as $oug) {
					$oug -> delete();
				}
				$owner -> delete();
			}
			
			
			/* Push sub-organisations to the stack */
			$subOrgUnit = Ou_model::list_by_ou_parent_id($ou -> ou_id);
			if(count($subOrgUnit) == 0) {
				if($ou -> ou_name != "root") { // Avoid deleting the root unit
					$ou -> delete();
				}
			} else {
				$stack[] = $ou;
				foreach($subOrgUnit as $l) {
					$stack[] = $l;
				}
			}
		}
	}
	
	/**
	 * Introduce dummy data
	 */
	static function dummyData() {
		$root = Ou_model::get_by_ou_name("root");
		
		/* Pick the first domain */
		$domain_id = "default";
		$domainList = ListDomain_model::list_by_domain_enabled('1');
		$domain_id = $domainList[0] -> domain_id;
		$serviceList = ListServiceDomain_model::list_by_domain_id($domain_id);
		$services = array();
		foreach($serviceList as $s) {
			if($s -> Service -> service_enabled == '1') {
				$services[] = $s -> service_id;
			}
		}

		/* Make orgUnits */
		$users = Ou_api::create("people", $root -> ou_id);
		$melbourne = Ou_api::create("melbourne", $users -> ou_id);
		$sydney = Ou_api::create("sydney", $users -> ou_id);
		$groups = Ou_api::create("groups", $root -> ou_id);
		
		/* 20 people.
		 * Search Criteria: 2012 Top 10" from https://online.justice.vic.gov.au/bdm/popular-names */
		$jack = AccountOwner_api::create($melbourne -> ou_id, "Example", "Jack", "jack", $domain_id, $services);
		$olivia = AccountOwner_api::create($melbourne -> ou_id, "Example", "Olivia", "olivia", $domain_id, $services);
		$william = AccountOwner_api::create($melbourne -> ou_id, "Example", "William", "william", $domain_id, $services);
		$charlotte = AccountOwner_api::create($melbourne -> ou_id, "Example", "Charlotte", "charlotte", $domain_id, $services);
		$oliver = AccountOwner_api::create($melbourne -> ou_id, "Example", "Oliver", "oliver", $domain_id, $services);
		$ruby = AccountOwner_api::create($melbourne -> ou_id, "Example", "Ruby", "ruby", $domain_id, $services);
		$ethan = AccountOwner_api::create($melbourne -> ou_id, "Example", "Ethan", "ethan", $domain_id, $services);
		$chloe = AccountOwner_api::create($melbourne -> ou_id, "Example", "Chloe", "chloe", $domain_id, $services);
		$thomas = AccountOwner_api::create($melbourne -> ou_id, "Example", "Thomas", "thomas", $domain_id, $services);
		$mia = AccountOwner_api::create($melbourne -> ou_id, "Example", "Mia", "mia", $domain_id, $services);
		$noah = AccountOwner_api::create($sydney -> ou_id, "Example", "Noah", "noah", $domain_id, $services);
		$emily = AccountOwner_api::create($sydney -> ou_id, "Example", "Emily", "emily", $domain_id, $services);
		$james = AccountOwner_api::create($sydney -> ou_id, "Example", "James", "james", $domain_id, $services);
		$ava = AccountOwner_api::create($sydney -> ou_id, "Example", "Ava", "ava", $domain_id, $services);
		$lucas = AccountOwner_api::create($sydney -> ou_id, "Example", "Lucas", "lucas", $domain_id, $services);
		$amelia = AccountOwner_api::create($sydney -> ou_id, "Example", "Amelia", "amelia", $domain_id, $services);
		$alexander = AccountOwner_api::create($sydney -> ou_id, "Example", "Alexander", "alexander", $domain_id, $services);
		$sophie = AccountOwner_api::create($sydney -> ou_id, "Example", "Sophie", "sophie", $domain_id, $services);
		$joshua = AccountOwner_api::create($sydney -> ou_id, "Example", "Joshua", "joshua", $domain_id, $services);
		$zoe = AccountOwner_api::create($sydney -> ou_id, "Example", "Zoe", "zoe", $domain_id, $services);
		
		/* Make groups */
		$everybody = UserGroup_api::create("global", "Employee Global", $groups -> ou_id, $domain_id);
		$manufacturing = UserGroup_api::create("manufacturing", "Manufacturing Team", $groups -> ou_id, $domain_id);
		$adminteam = UserGroup_api::create("adminteam", "Admin Team", $groups -> ou_id, $domain_id);
		$marketing = UserGroup_api::create("marketing", "Marketing Team", $groups -> ou_id, $domain_id);
		$sales = UserGroup_api::create("sales", "Sales Team", $groups -> ou_id, $domain_id);
		$executive = UserGroup_api::create("executive", "Executive Team", $groups -> ou_id, $domain_id);
		$logistics = UserGroup_api::create("logistics", "Logistics Team", $groups -> ou_id, $domain_id);
		
		/* Sub-groups */
		UserGroup_api::addchild($everybody -> group_id, $manufacturing -> group_id);
		UserGroup_api::addchild($everybody -> group_id, $adminteam -> group_id);
		UserGroup_api::addchild($adminteam -> group_id, $marketing -> group_id);
		UserGroup_api::addchild($marketing -> group_id, $sales -> group_id);
		UserGroup_api::addchild($adminteam -> group_id, $executive -> group_id);
		UserGroup_api::addchild($adminteam -> group_id, $logistics -> group_id);
		
		
		/* Group emmbership */
		AccountOwner_api::addtogroup($jack -> owner_id, $adminteam -> group_id);
		AccountOwner_api::addtogroup($olivia -> owner_id, $adminteam -> group_id);
		AccountOwner_api::addtogroup($william -> owner_id, $marketing -> group_id);
		AccountOwner_api::addtogroup($charlotte -> owner_id, $marketing -> group_id);
		AccountOwner_api::addtogroup($oliver -> owner_id, $sales -> group_id);
		AccountOwner_api::addtogroup($ruby -> owner_id, $sales -> group_id);
		AccountOwner_api::addtogroup($ethan -> owner_id, $sales -> group_id);
		AccountOwner_api::addtogroup($chloe -> owner_id, $logistics -> group_id);
		AccountOwner_api::addtogroup($thomas -> owner_id, $logistics -> group_id);
		AccountOwner_api::addtogroup($mia -> owner_id, $logistics -> group_id);
		AccountOwner_api::addtogroup($noah -> owner_id, $manufacturing -> group_id);
		AccountOwner_api::addtogroup($emily -> owner_id, $manufacturing -> group_id);
		AccountOwner_api::addtogroup($james -> owner_id, $manufacturing -> group_id);
		AccountOwner_api::addtogroup($ava -> owner_id, $logistics -> group_id);
		AccountOwner_api::addtogroup($lucas -> owner_id, $sales -> group_id);
		AccountOwner_api::addtogroup($amelia -> owner_id, $adminteam -> group_id);
		AccountOwner_api::addtogroup($alexander -> owner_id, $marketing -> group_id);
		AccountOwner_api::addtogroup($sophie -> owner_id, $manufacturing -> group_id);
		AccountOwner_api::addtogroup($chloe -> owner_id, $manufacturing -> group_id);
		AccountOwner_api::addtogroup($joshua -> owner_id, $manufacturing -> group_id);
		AccountOwner_api::addtogroup($zoe -> owner_id, $manufacturing -> group_id);
		
		/* And an executive team */
		AccountOwner_api::addtogroup($zoe -> owner_id, $executive -> group_id);
		AccountOwner_api::addtogroup($mia -> owner_id, $executive -> group_id);
		AccountOwner_api::addtogroup($thomas -> owner_id, $executive -> group_id);
		AccountOwner_api::addtogroup($james -> owner_id, $executive -> group_id);
	}
}