<?php
class UserGroup_view {
	function init() {
		Web::loadView("Page_view");
	}
	
	function view_html($data) {
		self::useTemplate("view", $data);
	}
	
	function create_html($data) {
		self::useTemplate("create", $data);
	}
	
	function rename_html($data) {
		self::useTemplate("rename", $data);
	}
	
	function addparent_html($data) {
		self::useTemplate("addparent", $data);
	}
	
	function addchild_html($data) {
		self::useTemplate("addchild", $data);
	}
	
	function adduser_html($data) {
		self::useTemplate("adduser", $data);
	}
	
	function useTemplate($template, $data) {
		$template = "UserGroup/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
	
	function error_html($data) {
		page_view::error_html($data);
	}
	
}