<?php 

require_once(dirname(__FILE__) . "/account_service.php");

class ldap_service extends account_service {
	private $service;
	private $ldap_url;
	private $ldap_user;
	private $ldap_root;
	private $ldap_pass;

	/**
	 * Construct a new LDAP service object
	 *
	 * @param string $ldap_url The URL of the ldap server. Looks like "ldaps://<hostname>:<port>".
	 * @param string $ldap_user The username to log in as. Usually "cn=admin,dc=..,dc=.." or "Administrator@<domain name>" for Active Directory.
	 * @param string $root The root of the domain. Looks like "dc=example,dc=com".
	 * @param string $ldap_pass The password to use when logging in to this server.
	 */
	function __construct(Service_model $service) {
		$this -> ldap_url = $service -> service_address;
		$this -> ldap_user = $service -> service_username;
		$this -> ldap_root = $service -> service_root;
		$this -> ldap_pass = $service -> service_password;

		/* Self-test */
		$this -> ldaptest();

		/* Match attributes */
		assert($this -> ldif_match('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 'attr-type-chars'));
		assert(!$this -> ldif_match('add:', 'attr-type-chars'));
		assert($this -> ldif_match('hi-there', 'attr-type-chars'));
		assert(!$this -> ldif_match('hi there', 'attr-type-chars'));
		assert(!$this -> ldif_match("hi there\n", 'attr-type-chars'));

		/* Match values */
		assert($this -> ldif_match("testing 123", 'safe-string'));
		assert($this -> ldif_match("The Quick Brown Fox Jumps Over the Lazy Dog \]\[!\"#$%&'()*+,./:;<=>?@\^_`{|}~-]", 'safe-string'));
		assert(!$this -> ldif_match(":hello: foo", 'safe-string'));
		assert(!$this -> ldif_match("<hello", 'safe-string'));
		assert(!$this -> ldif_match(" hi ", 'safe-string'));
	}

	function accountCreate(Account_model $a) {
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		$dn = "cn=" . $a -> account_login . "," .$ou;
		$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'add'),
					array('attr' => 'cn',			'value'=> $a -> account_login),
					array('attr' => 'uid',			'value'=> $a -> account_login),
					array('attr' => 'objectClass',	'value'=> 'account'),
					array('attr' => 'objectClass',	'value'=> 'posixAccount'),
					array('attr' => 'objectClass',	'value'=> 'top'),
					array('attr' => 'loginShell',	'value'=> '/bin/bash'),
					array('attr' => 'uidNumber',	'value'=> (int)$a -> owner_id),
					array('attr' => 'gidNumber',	'value'=> (int)$a -> owner_id),
					array('attr' => 'homeDirectory','value'=> '/home/'.$a-> account_login),
					array('attr' => 'gecos',		'value'=> $a -> AccountOwner -> owner_firstname . ' ' . $a -> AccountOwner -> owner_surname),
					array('attr' => 'userPassword',	'value'=> self::junkPassword())
				);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	private function dnFromOu($ou_id) {
		$ou = Ou_model::get($ou_id);
		if(!$ou) {
			throw new Exception("Organizational unit not found");
		}
		
		if($ou -> ou_name == "root") {
			return $this -> ldap_root;
		}
		return "ou=" . $ou -> ou_name . "," . $this -> dnFromOu($ou -> ou_parent_id);
	}
	
	function accountDelete($account_login, ListDomain_model $account_domain) {
		$a = $this -> ldapsearch($this -> ldap_root, false, "(uid=$account_login)");
		if(!isset($a[0]['dn'][0])) {
			throw new Exception("User not found.");
		}
		$dn = $a[0]['dn'][0];
		$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'delete'),
				);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	function accountUpdate(Account_model $a, $account_old_login, $owner_firstname, $owner_surname) {
		// TODO
		throw new Exception("Unimplemented");
	}

	function accountDisable(Account_model $a) {
		// TODO
		throw new Exception("Unimplemented");
	}

	function accountEnable(Account_model $a) {
		// TODO
		throw new Exception("Unimplemented");
	}

	function accountRelocate(Account_model $a, Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}

	function accountPassword(Account_model $a, $p) {
		// TODO
		throw new Exception("Unimplemented");
	}

	function recursiveSearch(Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}

	function ouCreate(Ou_model $o) {
		$dn = $this -> dnFromOu($o -> ou_id);
		$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'add'),
					array('attr' => 'objectClass',	'value'=> 'top'),
					array('attr' => 'objectClass',	'value'=> 'organizationalUnit')
				);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	function ouDelete($ou_name, ListDomain_model $d) {
		$a = $this -> ldapsearch($this -> ldap_root, false, "(ou=$ou_name)");
		if(!isset($a[0]['dn'][0])) {
			throw new Exception("Unit not found.");
		}
		$dn = $a[0]['dn'][0];
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'delete'),
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);		
	}
	
	function ouMove(Ou_model $o, Ou_model $parent) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function ouRename($ou_old_name, Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function ldaptest() {
		$this -> ldapsearch_enumerate($this -> ldap_root);
	}

	function ldapsearch($base, $onelevel = true, $filter = "") {
		$cmd =  sprintf("ldapsearch -x -D %s \\\n" .
				"-H %s \\\n" .
				"-w %s \\\n" .
				"-b %s \\\n" .
				($onelevel? "-s onelevel\\\n" : "") .
				($filter != ""? "%s" : ""),
				escapeshellarg($this -> ldap_user),
				escapeshellarg($this -> ldap_url),
				escapeshellarg($this -> ldap_pass),
				escapeshellarg($base),
				escapeshellarg($filter));
		$lines = array();
		exec($cmd, $lines, $ret);
		
		if($ret != 0) {
			throw new Exception("ldapsearch failed. Check your settings and make sure the server is online!");
		}
		
		return self::parseSearch($lines);
	}

	function ldapsearch_enumerate($ou) {
		return $this -> ldapsearch($ou, true);
	}

	private static function parseSearch($lines) {
		/* Unfold result of a search -- see http://www.ietf.org/rfc/rfc2849.txt */
		$prev = -1;
		foreach($lines as $num => $line) {
			if(strlen($line) != 0 && substr($line,0,1) != " ") {
				$prev = $num;
			} else if(strlen($line) == 0) {
				$prev = -1;
			} else {
				if($prev == -1) {
					$prev = $num;
				} else {
					$lines[$prev] .= substr($line, 1, strlen($line) - 1);
					unset($lines[$num]);
				}
			}
		}
		
		/* Parse result */
		$next = array();
		$object = array();
		foreach($lines as $line) {
			if($line == '') {
				if(count($next) != 0) {
					$object[] = $next;
				}
				$next = array();
			} elseif(substr($line, 0, 1) != "#") {
				$a = self::parse_attr($line);
				if(!isset($next[$a['attr']])) {
					$next[$a['attr']] = array();
				}
				$next[$a['attr']][] = $a['value'];
			}
		}
		return $object;
	}
	
	private static function parse_attr($line) {
		$c =  strpos($line, ":");
		$left = substr($line, 0, $c);
		$c++;
		$right = substr($line, $c, strlen($line) - $c);

		if(substr($right, 0, 1) == ":") {
			/* Spot encoded */
			$right = substr($right, 1, strlen($right) - 1);
			$right = trim($right);
			$right = base64_decode($right);
		} else {
			$right = trim($right);
		}
		return array('attr' => $left, 'value' => $right);
	}
	
	private function ldapmodify($ldif) {
		$temp_file = tempnam(sys_get_temp_dir(), 'ldap');

		/* Write LDIF to temp file */
		file_put_contents($temp_file, $ldif);

		/* Build command */
		$cmd =  sprintf("ldapmodify -H %s \\\n" .
				"-D %s \\\n" .
				"-w %s \\\n" .
				"-f %s",
				escapeshellarg($this -> ldap_url),
				escapeshellarg($this -> ldap_user),
				escapeshellarg($this -> ldap_pass),
				escapeshellarg($temp_file));

		/* Run command */
		system($cmd, $retval);
		unlink($temp_file);
		if($retval == 0) {
			return true;
		}
		
		echo $ldif;
		switch($retval) {
			case 66:
				throw new Exception("Operation not allowed on non-leaf");
			case 68:
				throw new Exception("Already exists");
			default:
				return false; /* Try again later */
		}
	}

	/**
	 * Generates a block of LDIF from a series of entries with 'attr' and 'value' set. Will throw an exception if there is something blatantly wrong with the input.
	 *
	 * @param array $attributes
	 */
	private function ldif_generate($attributes) {
		$outp = "";
		foreach($attributes as $value) {
			$outp .= $this -> ldif_attr($value['attr'], $value['value']) . "\n";
		}
		return $outp;
	}

	/**
	 * Escape an attribute value
	 *
	 * @param string $key
	 * @param string $value
	 */
	private function ldif_attr($attr, $value) {
		if(!$this -> ldif_match($attr, 'attr-type-chars')) {
			throw new Exception("Bad attribute: $attr");
		}

		if($attr == "unicodePwd") {
			/* Special Active Directory encoding */
			$value = "\"" . $value . "\"";
			$len = strlen($value);
			for ($i = 0; $i < $len; $i++) {
				$newpw .= "{$pw{$i}}\000";
			}
			$newpw = base64_encode($newpw);
			$value = $newpw;
		} else if(!$this -> ldif_match($value, 'safe-string') ||
				strpos($value, '\n') != false ||
				$value != trim($value) ||
				strlen($value) > 50 ||
				strpos($value, '\r') != false ||
				$attr == "userPassword") {
			$value = base64_encode($value);
			$attr .= ":";
		}
		return "$attr: $value";
	}

	/**
	 * Test validity of values based on RFC 2849
	 *
	 * @param string $string The string to match
	 * @param string $category The category to match it under
	 */
	private function ldif_match($string, $category) {
		switch($category) {
			case 'attr-type-chars':
				return preg_match("/^[a-zA-Z0-9\-]+$/", $string) == 1;
			case 'safe-init-char':
				return preg_match("/^[\x01-\x7F]+$/", $string) == 1 && preg_match("/[: <\r\n]/", $string) == 0;
			case 'safe-string':
				return $this -> ldif_match(substr($string, 0, 1), 'safe-init-char') && preg_match("/^[\x01-\x7F]+$/", $string);
			default:
				throw new Exception("Unknown match-type: $category");
		}
		return false;
	}
}
?>