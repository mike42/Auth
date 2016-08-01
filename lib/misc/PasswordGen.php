<?php
namespace Auth\misc;

class PasswordGen {
	static private $words;

	public static function init() {
		self::$words = explode("\n", file_get_contents(dirname(__FILE__)."/wordlist.txt"));
	}

	public static function generate() {
		while(1) {
			$id = rand(1, count(self::$words) - 1) - 1;
			$word = self::$words[$id];
			$pw = $word . rand(10, 99);
			if(strlen($pw) >= 8) {
				return $pw;
			}
		}
	}
}
