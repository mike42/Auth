<?php
/**
 * This class provides an interface for interacting with the ActionQueue.
 * The returned data is free from sensitive information such as passwords.
 *
 * @author Michael Billington <michael.billington@gmail.com>
 */
class ActionQueue_api {
	private static $modified = false;
	
	static function init() {
		Auth::loadClass("ListDomain_model");
		Auth::loadClass("ListServiceDomain_model");
		Auth::loadClass("Service_model");
		Auth::loadClass("ActionQueue_model");
	}
	
	/**
	 * Remove an entry from the ActionQueue.
	 * 
	 * @param $id The ID of the action to cancel
	 */
	static function cancel($aq_id) {
		if(!$aq = ActionQueue_model::get($aq_id)) {
			throw new Exception("ActionQueue entry not found.");
		}
		
		if($aq -> aq_complete != 0) {
			throw new Exception("The action is already complete.");
		}
		
		/* Go ahead and delete it */
		$aq -> delete();
		return $aq;
	}
	
	/**
	 * @return The number of pending items in the ActionQueue.
	 */
	static function count() {
		return ActionQueue_model::count();
	}
	
	function getNext() {
		
	}
	
	/**
	 * Simplest way to submit AQ item -- This adds an entry to be processed by every service.
	 * 
	 * @param string $action_type Type of action, from ListActionType.
	 * @param string $aq_target Target of the action
	 * @param string $aq_arg1 First argument (meaning depends on action_type)
	 * @param string $aq_arg2 Second argument (meaning depends on action_type)
	 * @param string $aq_arg3 Third argument (meaning depends on action_type)
	 */
	static public function submitToEverything($action_type = '', $aq_target = '', $aq_arg1 = '', $aq_arg2 = '', $aq_arg3 = '') {
		$services = Service_model::list_by_service_enabled('1');
		foreach($services as $service) {
			self::submit($service -> service_id, $service -> service_domain, $action_type, $aq_target, $aq_arg1, $aq_arg2, $aq_arg3);
		}
	}

	/**
	 * Submit an action to every service in a domain
	 * 
	 * @param string $domain_id	ID of the domain (from ListDomain) to apply action to.
	 * @param string $action_type Type of action, from ListActionType.
	 * @param string $aq_target Target of the action
	 * @param string $aq_arg1 First argument (meaning depends on action_type)
	 * @param string $aq_arg2 Second argument (meaning depends on action_type)
	 * @param string $aq_arg3 Third argument (meaning depends on action_type)
	 * @throws Exception
	 */
	static public function submitByDomain($domain_id = '', $action_type = '', $aq_target = '', $aq_arg1 = '', $aq_arg2 = '', $aq_arg3 = '') {
		if(!$domain = ListDomain_model::get($domain_id)) {
			throw new Exception("Domain invalid");
		}
		
		/* Repeat for all services under this domain */
		$domain -> populate_list_ListServiceDomain();
		foreach($domain -> list_ListServiceDomain as $sd) {
			if($sd -> Service -> service_enabled == '1') {
				$d = ($sd -> sd_secondary == 1)? $sd -> Service -> service_domain: $sd -> domain_id;
				self::submit($sd -> service_id, $d, $action_type, $aq_target, $aq_arg1, $aq_arg2, $aq_arg3);
			}
		}
	}
	
	/**
	 * Submit a single action for a given service & domain.
	 * 
	 * @param string $service_id Service to apply this action to, from Service table.
	 * @param string $domain_id	ID of the domain (from ListDomain) to apply action to.
	 * @param string $action_type Type of action, from ListActionType.
	 * @param string $aq_target Target of the action
	 * @param string $aq_arg1 First argument (meaning depends on action_type)
	 * @param string $aq_arg2 Second argument (meaning depends on action_type)
	 * @param string $aq_arg3 Third argument (meaning depends on action_type)
	 * @throws Exception
	 */
	static public function submit($service_id = '', $domain_id = '', $action_type = '', $aq_target = '', $aq_arg1 = '', $aq_arg2 = '', $aq_arg3 = '') {
		if(!$service = Service_model::get($service_id)) {
			throw new Exception("Invalid service");
		}
		
		if(!$domain = ListDomain_model::get($domain_id)) {
			throw new Exception("Invalid service");
		}
		
		if(!$at = ListActionType_model::get($action_type)) {
			throw new Exception("Invalid action type");
		}
		
		/* Add action to the queue */	
		$aq = new ActionQueue_model();
		$aq -> aq_attempts = 0;
		$aq -> aq_date = date("Y-m-d H:i:s", time() - 3); // Submit with slightly older timestamp (so that it can be picked up as "due" immediately.
		$aq -> service_id = $service -> service_id;
		$aq -> domain_id = $domain -> domain_id;
		$aq -> action_type = $at -> action_type;
		$aq -> aq_target = $aq_target;
		$aq -> aq_arg1 = $aq_arg1;
		$aq -> aq_arg2 = $aq_arg2;
		$aq -> aq_arg3 = $aq_arg3;
		$aq -> aq_complete = 0;
		$aq -> aq_id = $aq -> insert();
		self::$modified = true;
		return true; // No need to pass on this object
	}
	
	static public function getOverview() {
		return ActionQueue_model::get_overview();
	}
	
	/**
	 * Return an array containing up to N lines of output. Newest entries go first.
	 * 
	 * @param int $lines
	 */
	static public function getLog($lines) {
		/* Input check */
		$lines = (int)$lines;
		if($lines < 1) {
			return array();
		}
		
		/* Get logfile */
		$fn = Auth::getConfig('logfile');
		$command = sprintf("tail %s -n %s | tac", escapeshellarg($fn), escapeshellarg($lines));
		$lines = array();
		$ret = exec($command, $lines);
		return $lines;
	}
	
	static public function flush() {
		return ActionQueue_model::flush();
	}

	/**
	 * Start processing the ActionQueue if modified
	 */
	static public function start($force = false) {
		if(self::$modified || $force) {
			$dir = dirname(__FILE__) . "/../../maintenance/bin/";
			chdir($dir);
			system("./authqueue-start.sh");
		}
	}
	
	/**
	 * Process the ActionQueue, and return only when it is empty
	 */
	static public function runUntilEmpty($force = false) {
		if(self::$modified || $force) {
			$dir = dirname(__FILE__) . "/../../maintenance/bin/";
			chdir($dir);
			system("./authqueue.php -x -v");
			self::$modified = false;
		}
	}
}