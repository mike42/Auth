<?php
/**
 * Access components of an email address
 * 
 * @author Michael Billington
 */
class Provisioning_Email {
	public $local;
	public $domain;
	public $address;
	
	public function __construct($address) {
		$this -> address = $address;
		$i = strrpos($address, '@');
		$this -> local = substr($address, 0, $i);
		$i++;
		$this -> domain = substr($address, $i, strlen($address) - $i);
	}
}