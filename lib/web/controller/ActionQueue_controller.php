<?php
class ActionQueue_controller {
	function init() {
		Auth::loadClass("ActionQueue_api");
	}
	
	function view() {
		return array("current" => "ActionQueue", "AQ" => ActionQueue_api::getOverview());
	}
	
	function log() {
		return array("current" => "ActionQueue", "Log" => ActionQueue_api::getLog(500));
	}
}