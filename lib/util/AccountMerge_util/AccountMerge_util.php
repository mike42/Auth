<?php
require_once(dirname(__FILE__) . "/../util.php");

/**
 * @author mike
 *
 */
class AccountMerge_util extends util {
	private static $config;
	
	/**
	 * Initialise utility
	 */
	function init() {
		self::$util_name = "AccountMerge";
		self::verifyEnabled();
		
		Auth::loadClass("AccountOwner_api");
	}
	
	/**
	 * Load data for web interface
	 */
	function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		if(isset($_POST['owners'])) {
			$ownersTxt = $_POST['owners'];
		} else {
			$ownersTxt = "";
		}

		try {
			$found = array();
			$foundService = array();
			$listedOwner_ids = explode("\n", $ownersTxt);
			foreach($listedOwner_ids as $key => $owner_id) {
				$owner_id = trim($owner_id);
				if($owner_id != "") {
					/* Check that the AccountOwner really exists */
					$ao = AccountOwner_api::get((int)$owner_id);
					
					/* Check for duplicates on list*/
					if(isset($found[$owner_id])) {
						throw new Exception("Owner ID $owner_id can't be used twice!");
					}
					$found[$owner_id] = $ao;
					
					/* Check for duplicate use of accounts */
					$ao -> populate_list_Account();
					foreach($ao -> list_Account as $a) {
						/* Check for duplicate services */
						if(isset($foundService[$a -> service_id])) {
							throw new Exception("Two of the AccountOwners have accounts on the '".$a -> service_id . "' service.");
						}
						$foundService[$a -> service_id] = true;
					}
				}
			}
			
			/* Add new AccountOwner */
			if(isset($_POST['owner_id'])) {
				$owner_id = trim($_POST['owner_id']);
				
				/* Check that owner exists */
				$ao = AccountOwner_api::get((int)$owner_id);

				/* Check for duplicates on account list */
				if(isset($found[$owner_id])) {
					throw new Exception("Owner ID $owner_id is already on the list");
				}
				
				/* Check that we can merge (due do duplicate accounts) */
				$ao -> populate_list_Account();
				foreach($ao -> list_Account as $a) {
					if(isset($foundService[$a -> service_id])) {
						throw new Exception("One of the listed AccountOwners already has an account on the '".$a -> service_id . "' service.");
					}
					$foundService[$a -> service_id] = true;
				}
				
				/* Only add if valid */
				$ownersTxt .= $owner_id . "\n";
			}
			
			/* Check for prepare and merge */
			// TODO
			//print_r($_POST);
			
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		$data['owners'] = $ownersTxt;
		
		return $data;
	}
}