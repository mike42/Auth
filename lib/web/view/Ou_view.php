<?php
class Ou_view {
	function init() {
		Web::loadView("Page_view");
	}

	function view_html($data) {
		self::useTemplate("view", $data);
	}
	
	function create_html($data) {
		self::useTemplate("create", $data);
	}
	

	function useTemplate($template, $data) {
		$template = "Ou/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
	
	function error_html($data) {
		page_view::error_html($data);
	}
}