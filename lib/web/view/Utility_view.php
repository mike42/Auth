<?php
class Utility_view {
	function init() {
		Web::loadView("Page_view");
	}
	
	function error_html($data) {
		page_view::error_html($data);
	}
}
?>