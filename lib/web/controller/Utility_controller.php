<?php 
class Utility_controller {
	public static function view($utility_name) {
		return array('current' => 'Utility', 'util' => Auth::getConfig('Util'));
	}
}
?>