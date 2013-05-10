<?php
class Account_view {
	
	public static function init() {
		web::loadView("Page_view");
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
}