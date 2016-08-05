<?php
namespace Auth\web\controller;

use Auth\Auth;
use Auth\util\util;
use Auth\web\controller\Utility_controller;
use Auth\web\Web;

class Utility_controller {
	public static function view($utility_name) {
		return array('current' => 'Utility', 'util' => Auth::getConfig('Util'));
	}
}
?>