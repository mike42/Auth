<?php
require_once(dirname(__FILE__) . "/../util.php");

/**
 * @author morgan
 */
class Groupr_util extends util {
	private static $config;
	
	/**
	 * Initialise utility
	 */
	public static function init() {
		self::$util_name = "Groupr";
		self::verifyEnabled();
		Auth::loadClass("AccountOwner_api");
		
		// Use Auth::loadClass to load dependencies
	}
	
	/**
	 * Load data for web interface
	 */
	public static function admin() {
		$data = array("current" => "Utility", "util" => self::$util_name, "template" => "main");
		$service_id = 'ldap1';	
		
		if(isset($_POST['group_cn']) && isset($_POST['gname'])) {
			Auth::loadClass("PasswordGen");
			$data['message'] = $_POST['group_cn'];
			$group_cn = ($_POST['group_cn'] == '') ? $_POST['gname'] : $_POST['group_cn'];
			$group = usergroup_model::get_by_group_cn($group_cn);
			$group -> populate_list_OwnerUserGroup();
			foreach($group -> list_OwnerUserGroup as $oug) {
				$preset = passwordGen::Generate();
				$account = Account_model::get_by_service_owner_unique($service_id, $oug -> owner_id);
				if ($account){
					AccountOwner_api::pwreset($oug -> AccountOwner -> owner_id, $preset );
					$passwrd [$account -> account_login] = $preset;
									
					
				}
					
			}
			$data['passwrd'] = $passwrd;
		}
		
	return $data;
	}
	
	public static function james() {
			
	}
	/**
	 * Do any maintenance tasks
	 */
	public static function doMaintenance() {
		
		// Do tasks here
		throw new Exception("Unimplemented");
	}
}
