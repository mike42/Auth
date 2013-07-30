<?php
require_once(dirname(__FILE__) . "/../util.php");

/**
 * @author mike
 *
 */
class Cleanup_util extends util {
	private static $config;

	/**
	 * Initialise utility
	 */
	public static function init() {
		self::$util_name = "Cleanup";
		self::verifyEnabled();
		
		Auth::loadClass("Ou_api");
		Auth::loadClass("ActionQueue_api");
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
					default:
						throw new Exception("Unknown action: $action");
				}
			} catch(Exception $e) {
				$data['message'] = $e -> getMessage();
			}
		}		
		
		$data['service-enabled'] = Service_model::list_by_service_enabled('1');
		$data['service-disabled'] = Service_model::list_by_service_enabled('0');		
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
}