<?php
namespace Auth\web\controller;

use Auth\api\ActionQueue_api;
use Auth\Auth;
use Auth\web\controller\ActionQueue_controller;
use Auth\web\Web;

class ActionQueue_controller {
	public static function init() {
		Auth::loadClass("ActionQueue_api");
	}
	
	public static function view() {
		return array("current" => "ActionQueue", "AQ" => ActionQueue_api::getOverview());
	}
	
	public static function log() {
		return array("current" => "ActionQueue", "Log" => ActionQueue_api::getLog(500));
	}
}