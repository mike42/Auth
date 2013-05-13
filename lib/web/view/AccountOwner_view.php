<?php
class AccountOwner_view {
	function init() {
		Web::loadView("Page_view");
	}
	
	function create_html($data) {
		self::useTemplate("create", $data);
	}
	
	function view_html($data) {
		self::useTemplate("view", $data);
	}
	
	function error_html($data) {
		page_view::error_html($data);
	}
	
	function useTemplate($template, $data) {
		$template = "AccountOwner/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
	
	function pwreset_html($data) {
		self::useTemplate("pwreset", $data);
	}
	
	function move_html($data) {
		self::useTemplate("move", $data);
	}
	
	function rename_html($data) {
		self::useTemplate("rename", $data);
	}
	
	function addgroup_html($data) {
		self::useTemplate("addgroup", $data);
	}
}
?>