<?php
class Account_view {
	
	public static function init() {
		web::loadView("Page_view");
	}
	
	public static function create_html($data) {
		self::useTemplate("create", $data);
	}
	
	public static function view_html($data) {
		self::useTemplate("view", $data);
	}
	
	public static function error_html($data) {
		page_view::error_html($data);
	}
	
	function useTemplate($template, $data) {
		$template = "Account/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
	
	function search_json($data) {
		echo json_encode($data['Accounts']);
		exit();
	}
}