<?php

class ReceiptPrinter {
	public static function init() {
		require_once(dirname(__FILE__) . "/../vendor/escpos-php/escpos.php");
	}

	public static function pwresetReceipt(AccountOwner_model $owner, $password) {
		throw new Exception("Receipt print failed");
	}
}
