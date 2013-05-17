<?php 
class Utility_controller {
	function view($utility_name) {
		return array('current' => 'Utility', 'util' => Auth::getConfig('Util'));
	}
}
?>