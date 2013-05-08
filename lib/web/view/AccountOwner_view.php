<?php
class AccountOwner_view {
	function create_html($data) {
		self::useTemplate("create", $data);
	}
	
	function useTemplate($template, $data) {
		$template = "AccountOwner/".$template;
		include(dirname(__FILE__) . "/layout/htmlLayout.inc");
	}	
}
?>