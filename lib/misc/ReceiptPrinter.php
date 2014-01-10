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
		
		if(!$fp = fsockopen(self::$conf['ip'], self::$conf['port'], $errno, $errstr, 2)) {
			throw new Exception("Couldn't connect to receipt printer: $errno $errstr");
		}
		
		/* Header */
		$printer = new escpos($fp);
		$printer -> set_justification(escpos::JUSTIFY_CENTER);
		$printer -> set_emphasis(true);
		$printer -> text(self::$conf['header'] . "\n");
		$printer -> set_emphasis(false);
		$printer -> feed();
		$printer -> text("User Account Information\n");
		$printer -> feed(2);
		$printer -> set_justification(escpos::JUSTIFY_LEFT);
		
		/* User info */
		$barcode = "";
		$seen = array();
		$printer -> text("User Account:\n  " . $owner -> owner_firstname . " " . $owner -> owner_surname . "\n\n");
		$printer -> text("Login name(s):\n");
		foreach($owner -> list_Account as $acct) {
			if(!isset($seen[$acct -> account_login])) {
				$printer -> text("  " . $acct -> account_login . "\n");
				$seen[$acct -> account_login] = true;
				if(is_numeric($acct -> account_login) && ($barcode == "" || strlen($acct -> account_login) < strlen($barcode))) {
					$barcode = $acct -> account_login;
				}
			}
		}
		$printer -> feed();
		$printer -> text("Password:\n  $password\n");
		$printer -> feed(2);
		
		/* Footer */
		$printer -> text(self::$conf['footer']  . "\n");
		$printer -> feed(2);
		
		/* Barcode */
		if($barcode != "") {
			$printer -> set_justification(escpos::JUSTIFY_CENTER);
			$printer -> barcode($barcode, escpos::BARCODE_CODE39);
			$printer -> feed();
			$printer -> text($barcode);
			$printer -> feed(2);
			$printer -> set_justification(escpos::JUSTIFY_LEFT);
		}
		$printer -> cut();
		
		fclose($fp);
	}
}
