<?php
/**
 * This class provides an interface for interacting with the ActionQueue.
 * The returned data is free from sensitive information such as passwords.
 *
 * @author Michael Billington <michael.billington@gmail.com>
 */
class ActionQueue_api {
	function cancel(int $id) {
		
	}
	
	function count() {
		return 0;
	}
	
	static public function submitBatch($action_type = '', $aq_target = '', $aq_arg1 = '', $aq_arg2 = '', $aq_arg3 = '') {
		/* Repeat for all services & domains */
		throw new Exception("Unimplemented");
	}
	
	static public function submit($service_id = '', $domain_id = '', $action_type = '', $aq_target = '', $aq_arg1 = '', $aq_arg2 = '', $aq_arg3 = '') {
		/* Add a single action to the queue */	
		
		throw new Exception("Unimplemented");
		
		/*
		$aq = new ActionQueue_model();
		$aq -> aq_attempts = 0;
		$aq -> aq_date = "";
		$aq -> service_id = $service_id;
		$aq -> domain_id = $domain_id;
		$aq -> action_type = $action_type;
		$aq -> aq_target = $aq_target;
		$aq -> aq_arg1 = $aq_arg1;
		$aq -> aq_arg2 = $aq_arg2;
		$aq -> aq_arg3 = $aq_arg3;
		$aq -> aq_complete = 0;
		$aq -> aq_id = $aq -> insert();
		*/
	}
}