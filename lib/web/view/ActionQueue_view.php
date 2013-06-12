<?php
class ActionQueue_view {
	function init() {
		Web::loadView("Page_view");
	}
	
	function view_html($data) {
		self::useTemplate('view', $data);
	}
	
	function error_html($data) {
		page_view::error_html($data);
	}
	
	function log_html($data) {
		self::useTemplate('log', $data);
	}
	
	function useTemplate($template, $data) {
		$template = "ActionQueue/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
}