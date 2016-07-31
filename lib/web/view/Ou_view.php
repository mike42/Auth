<?php
use Auth\web\Web;

class Ou_view {
	public static function init() {
		Web::loadView("Page_view");
	}

	public static function view_html($data) {
		self::useTemplate("view", $data);
	}
	
	public static function move_html($data) {
		self::useTemplate("move", $data);
	}
	
	public static function rename_html($data) {
		self::useTemplate("rename", $data);
	}
	
	public static function create_html($data) {
		self::useTemplate("create", $data);
	}
	
	public static function useTemplate($template, $data) {
		$template = "Ou/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
	
	public static function error_html($data) {
		page_view::error_html($data);
	}
}