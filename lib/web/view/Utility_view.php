<?php
use Auth\web\Web;

class Utility_view {
	public static function init() {
		Web::loadView("Page_view");
	}
	
	public static function view_html($data) {
		self::useTemplate('home', $data);
	}
	
	public static function error_html($data) {
		page_view::error_html($data);
	}
	
	private static function useTemplate($template, $data) {
		$template = "Utility/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
	
	public static function admin_html($data) {
		self::useTemplate("../../../../util/" . $data['util'] . "_util/layout/" . $data['template'], $data);
	}
}
?>