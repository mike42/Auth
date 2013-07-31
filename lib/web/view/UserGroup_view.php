<?php
class UserGroup_view {
	public static function init() {
		Web::loadView("Page_view");
	}
	
	public static function view_html($data) {
		self::useTemplate("view", $data);
	}
	
	public static function create_html($data) {
		self::useTemplate("create", $data);
	}
	
	public static function rename_html($data) {
		self::useTemplate("rename", $data);
	}
	
	public static function move_html($data) {
		self::useTemplate("move", $data);
	}
	
	public static function addparent_html($data) {
		self::useTemplate("addparent", $data);
	}
	
	public static function addchild_html($data) {
		self::useTemplate("addchild", $data);
	}
	
	public static function adduser_html($data) {
		self::useTemplate("adduser", $data);
	}
	
	private static function useTemplate($template, $data) {
		$template = "UserGroup/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
	
	public static function error_html($data) {
		page_view::error_html($data);
	}
	
	public static function search_json($data) {
		echo json_encode($data['UserGroups']);
		exit();
	}
	
	public static function error_json($data) {
		self::error_html($data);
	}
}