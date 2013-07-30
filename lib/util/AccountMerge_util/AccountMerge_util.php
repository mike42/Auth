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
	public static function init() {
		self::$util_name = "AccountMerge";
		self::verifyEnabled();
		
		Auth::loadClass("AccountOwner_api");
	}
	
	/**
	 * Load data for web interface
	 */
	public static function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		if(isset($_POST['owners'])) {
			$ownersTxt = $_POST['owners'];
		} else {
			$ownersTxt = "";
		}
		
		/* Check for prepare and merge */
		$merge = $prepare = false;
		if(isset($_POST['action'])) {
			if($_POST['action'] == "merge") {
				$merge = true;
				// TODO: Check for empty ActionQueue
			} else if($_POST['action'] == "prepare") {
				$prepare = true;
			}
		}

		try {
			$found = array();
			$foundService = array();
			$listedOwner_ids = explode("\n", $ownersTxt);
			$ou_id = -1;
			$owner_firstname = $owner_surname = false;
			foreach($listedOwner_ids as $key => $owner_id) {
				$owner_id = trim($owner_id);
				if($owner_id != "") {
					/* Check that the AccountOwner really exists */
					$ao = AccountOwner_api::get((int)$owner_id);
					
					/* Check for duplicates on list*/
					if(isset($found[$owner_id])) {
						throw new Exception("Owner ID $owner_id can't be used twice!");
					}
					
					/* Check for duplicate use of accounts */
					$ao -> populate_list_Account();
					foreach($ao -> list_Account as $a) {
						/* Check for duplicate services */
						if(isset($foundService[$a -> service_id])) {
							throw new Exception("Two of the AccountOwners have accounts on the '".$a -> service_id . "' service.");
						}
						$foundService[$a -> service_id] = true;
					}
					
					if($merge) {
						/* Check for no groups */
						$ao -> populate_list_OwnerUserGroup();
						if(count($ao -> list_OwnerUserGroup) != 0) {
							throw new Exception("Cannot merge owner $owner_id because it is in some groups (run 'prepare' first)");
						}
						
						/* Check for same OU */
						if($ou_id == -1) {
							$ou_id = $ao -> ou_id;
						} else if($ou_id != $ao -> ou_id) {
							throw new Exception("Cannot merge $owner_id because it is in a different organizational unit (run 'prepare' first)");
						}
						
						if($owner_firstname == false && $owner_surname == false) {
							$owner_firstname = $ao -> owner_firstname;
							$owner_surname = $ao -> owner_surname;
						} elseif($owner_firstname != $ao -> owner_firstname || $owner_surname != $ao -> owner_surname) {
							throw new Exception("Cannot merge $owner_id because they have a different name - Expected \"$owner_firstname $owner_surname\" but found \"" . $ao -> owner_firstname . " " . $ao -> owner_surname . "\" (run 'prepare' first)");
						}
					}
					
					$found[$owner_id] = $ao;
				}
			}
			
			/* Add new AccountOwner */
			if(isset($_POST['owner_id'])) {
				$owner_id = trim($_POST['owner_id']);
				
				if($owner_id != "") {
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
					
					if($merge) { // block copied from above
						/* Check for no groups */
						$ao -> populate_list_UserGroup();
						if(count($ao -> list_UserGroup) != 0) {
							throw new Exception("Cannot merge owner $owner_id because it is in some groups (run 'prepare' first)");
						}
					
						/* Check for same OU */
						if($ou_id == -1) {
							$ou_id = $ao -> ou_id;
						} else if($ou_id != $ao -> ou_id) {
							throw new Exception("Cannot merge $owner_id because it is in a different organizational unit (run 'prepare' first)");
						}
					}
					
					/* Only add if valid */
					$found[$owner_id] = $ao;
					$ownersTxt .= $owner_id . "\n";
				}
			}
			
			if($prepare) {
				/* Move into the same OU, remove all groups */
				$model = array_shift($found);
				print_r($model);
				foreach($found as $ao) {
					if($ao -> ou_id != $model -> ou_id) {
						AccountOwner_api::move($ao -> owner_id, $model -> ou_id);
					}
					
					if($ao -> owner_firstname != $model -> owner_firstname || $ao -> owner_surname != $model -> owner_surname) {
						AccountOwner_api::rename($ao -> owner_id, $model -> owner_firstname, $model -> owner_surname);	
					}
				}
			} else if($merge) {
				/* Actually turn into the same user account */
				$model = array_shift($found);
				foreach($found as $ao) {
					foreach($ao -> list_Account as $a) {
						$a -> owner_id = $model -> owner_id;
						$a -> update();
					}
					$ao -> delete();
				}
			}
		} catch(Exception $e) {
			$data['message'] = $e -> getMessage();
		}
		$data['owners'] = $ownersTxt;
		
		return $data;
	}
	
	public static function doMaintenance() {
		throw new Exception("Unimplemented");
	}
}