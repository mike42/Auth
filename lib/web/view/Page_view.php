<?php
namespace Auth\web\view;

use Auth\Auth;
use Auth\web\view\Page_view;
use Auth\web\Web;

class Page_view {
	public static function view_html($data) {
		self::useTemplate('home', $data);
	}

	public static function error_html($data) {
		self::useTemplate($data['error'], $data);
	}
	
	private static function useTemplate($template, $data) {
		$template = "Page/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
}

?>