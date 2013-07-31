<?php
class ActionQueue_view {
	public static function init() {
		Web::loadView("Page_view");
	}
	
	public static function view_html($data) {
		self::useTemplate('view', $data);
	}
	
	public static function error_html($data) {
		page_view::error_html($data);
	}
	
	public static function log_html($data) {
		self::useTemplate('log', $data);
	}
	
	public static function useTemplate($template, $data) {
		$template = "ActionQueue/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
}