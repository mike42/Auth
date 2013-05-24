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
		$this -> service = $service;

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
		$dn = "cn=" . $a -> account_login . "," . $ou;
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

	function accountDelete($account_login, ListDomain_model $account_domain) {
		$dn = $this -> dnFromSearch("(uid=$account_login)");
		
		$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'delete'),
				);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	function accountUpdate(Account_model $a, $account_old_login) {
		$dn = $this -> dnFromSearch("(uid=$account_old_login)");
		
		$ldif = "";
		$gecos = $a -> AccountOwner -> owner_firstname . ' ' . $a -> AccountOwner -> owner_surname;
		if(isset($b[0]['gecos']) && $b[0]['gecos'] != $gecos) {
			/* Replace fullname if required */
			$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'modify'),
					array('attr' => 'replace',		'value'=> 'gecos'),
					array('attr' => 'gecos', 		'value'=> $gecos)
			);
			$ldif .= $this -> ldif_generate($map);
		}
		
		if($account_old_login != $a -> account_login) {		
			/* UID */
			$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'modify'),
					array('attr' => 'replace',		'value'=> 'uid'),
					array('attr' => 'uid',			'value'=> $a -> account_login),
			);
			$ldif .= $this -> ldif_generate($map);
			
			/* Home directory */
			$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'modify'),
					array('attr' => 'replace',		'value'=> 'homeDirectory'),
					array('attr' => 'homeDirectory','value'=> '/home/'.$a-> account_login),
			);
			$ldif .= $this -> ldif_generate($map);

			/* Change 'cn' in directory if we are renaming */
			$newrdn = "cn=" . $a -> account_login;
			$superior = $this -> dnFromOu($a -> AccountOwner -> ou_id);

			$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'modrdn'),
					array('attr' => 'newrdn',		'value'=> $newrdn),
					array('attr' => 'deleteoldrdn',	'value'=> '0'),
					array('attr' => 'newsuperior',	'value'=> $superior)
			);
			$ldif .= $this -> ldif_generate($map);
		}
		
		return $this -> ldapmodify($ldif);
	}

	function accountDisable(Account_model $a) {
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		$dn = "cn=" . $a -> account_login . "," .$ou;
		// TODO
		throw new Exception("Unimplemented");
	}

	function accountEnable(Account_model $a) {
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		$dn = "cn=" . $a -> account_login . "," .$ou;
		// TODO
		throw new Exception("Unimplemented");
	}

	function accountRelocate(Account_model $a, Ou_model $o) {
		/* Locate user's current place */
		$dn = $this -> dnFromSearch("(uid=".$a -> account_login . ")");
		
		/* New details */
		$newsuperior = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		$newrdn = "cn=" . $a -> account_login;
		
		/* Submit change */
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'modrdn'),
				array('attr' => 'newrdn',		'value'=> $newrdn),
				array('attr' => 'deleteoldrdn',	'value'=> '1'),
				array('attr' => 'newsuperior',	'value'=> $newsuperior)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	function accountPassword(Account_model $a, $p) {
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		$dn = "cn=" . $a -> account_login . "," .$ou;
		$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'modify'),
					array('attr' => 'replace',		'value'=> 'userPassword'),
					array('attr' => 'userPassword',	'value'=> $p)
				);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	function recursiveSearch(Ou_model $o) {
		$base = $this -> dnFromOu($o -> ou_id);
		$objects = $this -> ldapsearch($base);
		foreach($objects as $object) {
			if(!isset($object['dn'])) {
				continue;
			}
			
			if(!isset($object['objectClass'])) {
				continue;	
			}

			$objectClass = $object['objectClass'];
			if(in_array('organizationalUnit', $objectClass)) {
				/* Hit organizational unit */
				$ou_name = $object['ou'][0];
				if(!$ou = Ou_model::get_by_ou_name($ou_name)) {
					/* Need to create */
					$ou = Ou_api::create($ou_name, $o -> ou_id);
				}

				/* Recurse */
				ActionQueue_api::submit($this -> service -> service_id, $this -> service -> service_domain, 'recursiveSea', $ou -> ou_name);
			} elseif(in_array('posixAccount', $objectClass)) {
				/* Found user account */
				$account_login = $object['uid'][0];
				
				/* Crude reconstruction of firstname/lastname */
				$fullname = explode(' ', $object['gecos'][0]);
				$owner_firstname = array_shift($fullname);
				$owner_surname = implode(' ', $fullname);
				if(!$account = Account_model::get_by_account_login($account_login, $this -> service -> service_id, $this -> service -> service_domain)) {
					/* Does not exist on default domain (this LDAP service only supports one domain) */
					$owner = AccountOwner_api::create($o -> ou_id, $owner_firstname, $owner_surname, $account_login, $this -> service -> service_domain, array($this -> service -> service_id));
				}
			} elseif(in_array('groupOfNames', $objectClass)) {
				/* Found Group */
				$group_cn = $object['cn'][0];
				$group_name = $object['description'][0];
				if(!$group = UserGroup_model::get_by_group_cn($group_cn)) {
					$group = UserGroup_api::create($group_cn, $group_name, $o -> ou_id, $this -> service -> service_domain);
				}

				foreach($object['member'] as $member) {
					echo $member . "\n";
				}
			} else {
				
			}
		}
				
		return true;
	}

	function groupCreate(UserGroup_model $g) {
		// TODO: Existence check for groups.
		
		$ou = $this -> dnFromOu($g -> ou_id);
		$dn = "cn=" . $g -> group_cn . "," .$ou;
		$description = $g -> group_name;
		
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'add'),
				array('attr' => 'objectClass',	'value'=> 'groupOfNames'),
				array('attr' => 'cn',			'value'=> $g -> group_cn),
				array('attr' => 'description',	'value'=> $description),
				array('attr' => 'member',		'value'=> 'cn=invalid,ou=system,'. $this -> ldap_root), /* Dummy entry */
			);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	function groupDelete($group_cn, $group_domain) {
		$dn = $this -> dnFromSearch("(cn=$group_cn)");
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'delete')
				);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);		
	}

	function groupJoin(Account_model $a, UserGroup_model $g) {
		$groupOu = $this -> dnFromOu($g -> ou_id);
		$groupDn = "cn=" . $g -> group_cn . "," .$groupOu;
		
		$AccountOu = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		$AccountDn = "cn=" . $a -> account_login . "," .$AccountOu;

		$map = array(
				array('attr' => 'dn',			'value'=> $groupDn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'add',			'value'=> 'member'),
				array('attr' => 'member',		'value'=> $AccountDn)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	function groupLeave(Account_model $a, UserGroup_model $g) {
		$groupOu = $this -> dnFromOu($g -> ou_id);
		$groupDn = "cn=" . $g -> group_cn . "," .$groupOu;
		
		$AccountOu = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		$AccountDn = "cn=" . $a -> account_login . "," .$AccountOu;
		
		$map = array(
				array('attr' => 'dn',			'value'=> $groupDn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'delete',		'value'=> 'member'),
				array('attr' => 'member',		'value'=> $AccountDn)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	function groupAddChild(UserGroup_model $parent, UserGroup_model $child) {
		$parentOu = $this -> dnFromOu($parent -> ou_id);
		$parentDn = "cn=" . $parent -> group_cn . "," .$parentOu;
		
		$childOu = $this -> dnFromOu($child -> ou_id);
		$childDn = "cn=" . $child -> group_cn . "," .$childOu;
		
		$map = array(
				array('attr' => 'dn',			'value'=> $parentDn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'add',			'value'=> 'member'),
				array('attr' => 'member',		'value'=> $childDn)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	function groupDelChild(UserGroup_model $parent, UserGroup_model $child) {
		$parentOu = $this -> dnFromOu($parent -> ou_id);
		$parentDn = "cn=" . $parent -> group_cn . "," .$parentOu;
		
		$childOu = $this -> dnFromOu($child -> ou_id);
		$childDn = "cn=" . $child -> group_cn . "," .$childOu;
		
		$map = array(
				array('attr' => 'dn',			'value'=> $parentDn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'delete',		'value'=> 'member'),
				array('attr' => 'member',		'value'=> $childDn)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	function groupMove(UserGroup_model $g, Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	function groupRename(UserGroup_model $g, $ug_old_cn) {
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
		/* Locate Ou's current place */
		$dn = $this -> dnFromSearch("(ou=".$ou_name . ")");

		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'delete'),
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);		
	}
	
	function ouMove(Ou_model $o, Ou_model $parent) {
		/* Locate Ou's current place */
		$dn = $this -> dnFromSearch("(ou=".$o -> ou_name . ")");
		
		$newsuperior = $this -> dnFromOu($parent -> ou_id);

		/* Submit change */
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'modrdn'),
				array('attr' => 'newrdn',		'value'=> 'ou=' . $o -> ou_name),
				array('attr' => 'deleteoldrdn',	'value'=> '1'),
				array('attr' => 'newsuperior',	'value'=> $newsuperior),
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	function ouRename($ou_old_name, Ou_model $o) {
		// TODO
		throw new Exception("Unimplemented");
	}
	
	/**
	 * Get the dn of the first entry returned from an ldapsearch
	 * 
	 * @param string $filter A filter. Example: "(uid=jbloggs)"
	 * @throws Exception
	 */
	function dnFromSearch($filter) {
		$a = $this -> ldapsearch($this -> ldap_root, false, $filter);
		if(!isset($a[0]['dn'][0])) {
			throw new Exception("Nothing found while searching $filter.");
		} else if(isset($a[1]['dn'][0])) {
			throw new Exception("Duplicate found while searching $filter. Log in to the service and delete one to administer this object!");
		}
		return $a[0]['dn'][0];
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
			case 32:
				throw new Exception("No such object");
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
		return $outp."\n";
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