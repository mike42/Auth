<?php
class Utility_view {
	function init() {
		Web::loadView("Page_view");
	}
	
	function view_html($data) {
		self::useTemplate('home', $data);
	}
	
	function error_html($data) {
		page_view::error_html($data);
	}
	
	function useTemplate($template, $data) {
		$template = "Utility/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
}
?>