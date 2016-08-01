<?php
use Auth\Auth;

class ReceiptPrinter {
	private static $conf; /* Config */
	
	public static function init() {
		require_once(dirname(__FILE__) . "/../vendor/escpos-php/Escpos.php");
		self::$conf = Auth::getConfig(__CLASS__);
	}

	public static function pwresetReceipt(AccountOwner_model $owner, $password) {
		if(!isset(self::$conf['ip']) || self::$conf['ip'] == "0.0.0.0") {
			return false;
		}
		try {
			$connector = new NetworkPrintConnector(self::$conf['ip'], self::$conf['port']);
			$profile = SimpleCapabilityProfile::getInstance();
			$printer = new Escpos($connector, $profile);

			/* Header */
			$printer -> setJustification(Escpos::JUSTIFY_CENTER);
			if(isset(self::$conf['logo']) && file_exists(self::$conf['logo'])) {
				try {
					/* Include top image if set & available */
					$logofile = self::$conf['logo'];
					$ser = $logofile . ".ser";
					if(file_exists($ser)) {
						$img = unserialize(file_get_contents($ser));
					} else {
						$img = new EscposImage($logofile);
						@file_put_contents($ser, serialize($img)); // Attempt to cache
					}
					$printer -> bitImage($img);
				} catch (Exception $e) {
					trigger_error($e -> getMessage());
				}
			}
			$printer -> setEmphasis(true);
			$printer -> text(self::$conf['header'] . "\n");
			$printer -> setEmphasis(false);
			$printer -> feed();
			$printer -> text("User Account Information\n");
			$printer -> feed(2);
			$printer -> setJustification(Escpos::JUSTIFY_LEFT);

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
			$printer -> feed();

			/* Barcode */
			if($barcode != "") {
				$printer -> setJustification(Escpos::JUSTIFY_CENTER);
				$printer -> barcode($barcode, Escpos::BARCODE_CODE39);
				$printer -> feed();
				$printer -> text($barcode);
				$printer -> feed(1);
				$printer -> setJustification(Escpos::JUSTIFY_LEFT);
			}
			$printer -> cut();
			$printer -> close();
		} catch(Exception $e) {
			trigger_error($e -> getMessage()); // Should be logged some-place for troubleshooting.
			return false;
		}
	}
}
