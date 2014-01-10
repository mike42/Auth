<?php

class ReceiptPrinter {
	private static $conf; /* Config */
	
	public static function init() {
		require_once(dirname(__FILE__) . "/../vendor/escpos-php/escpos.php");
		self::$conf = Auth::getConfig(__CLASS__);
	}

	public static function pwresetReceipt(AccountOwner_model $owner, $password) {
		if(!isset(self::$conf['ip']) || self::$conf['ip'] == "0.0.0.0") {
			return false;
		}
		
		if(!$fp = fsockopen(self::$conf['ip'], self::$conf['port'])) {
			throw new Exception("Couldn't connect to receipt printer");
		}
		
		$printer = new escpos($fp);
		$printer -> text("Test print");
		$printer -> feed();
		$printer -> cut();
		
		fclose($fp);
	}
}
