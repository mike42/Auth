<?php
/**
 * This class provides an interface for managing accounts in the local database.
 * It ensures that changes are pushed onto the ActionQueue.
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class Account_api {	
	public function init() {
		Auth::loadClass("AccountOwner_model");
		Auth::loadClass("Account_model");
	}
	
	
}

?>