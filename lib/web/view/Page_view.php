<?php 

class Page_view {
	function view_html($data) {
		self::useTemplate('home', $data);
	}

	function error_html($data) {
		self::useTemplate($data['error'], $data);
	}
	
	function useTemplate($template, $data) {
		$template = "Page/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}
}

?>