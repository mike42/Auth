<?php 

require_once(dirname(__FILE__) . "/account_service.php");

class ldap_service extends account_service {
	private $service;
	private $ldap_url;
	private $ldap_user;
	private $ldap_root;
	private $ldap_pass;
	
	/* To be re-defined as needed */
	protected $dummyGroupMember;
	protected $groupObjectClass;
	protected $userObjectClass;
	protected $passwordAttribute;

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

		/* Some defaults */
		$this -> dummyGroupMember = 'cn=invalid,ou=system,'. $this -> ldap_root; // Only works without constraints!
		$this -> groupObjectClass = 'groupOfNames';
		$this -> userObjectClass = 'posixAccount';
		$this -> passwordAttribute = 'userPassword';
		$this -> loginAttribute = 'cn';

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

	/**
	 * Make a new user account on this service.
	 *
	 * @param Account_model $a The account to create
	 */
	public function accountCreate(Account_model $a) {
		/* Check and figure out dn */
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if($dn = $this -> dnFromSearch("(".$this -> loginAttribute."=" . $a -> account_login . ")", $ou)) {
			throw new Exception("Skipping account creation, account exists");
		}
		$dn = "cn=" . $a -> account_login . "," . $ou;

		/* Create specified account */
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
	
	/**
	 * Delete a user account from this service.
	 *
	 * @param string $account_login The login of the deleted account
	 * @param ListDomain_model $d The domain name of the deleted account
	 * @param Ou_model $o Unit to search for the login in.
	 */
	public function accountDelete($account_login, ListDomain_model $d, Ou_model $o) {
		/* Check & locate */
		$ou = $this -> dnFromOu($o -> ou_id);
		if(!$dn = $this -> dnFromSearch("(".$this -> loginAttribute."=$account_login)", $ou)) {
			throw new Exception("Skipping account deletion, account doesn't seem to exist");
		}

		/* Delete */
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'delete'),
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Update the login and display name for an account.
	 *
	 * @param Account_model $a The account to update
	 * @param string $account_old_login The login to search for (may be different to the one stored currently, if it has been changed)
	 	*/
	public function accountUpdate(Account_model $a, $account_old_login) {
		/* Locate account */
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if(!$dn = $this -> dnFromSearch("(".$this -> loginAttribute."=$account_old_login)", $ou)) {
			throw new Exception("Skipping account update, account doesn't seem to exist.");
		}
		$ldif = "";
		
		/* Replace fullname */
		$gecos = $a -> AccountOwner -> owner_firstname . ' ' . $a -> AccountOwner -> owner_surname;
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'replace',		'value'=> 'gecos'),
				array('attr' => 'gecos', 		'value'=> $gecos)
			);
		$ldif .= $this -> ldif_generate($map);

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

			/* Also change 'cn' in directory if we are renaming */
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
	
	/**
	 * Disable a user account.
	 *
	 * @param Account_model $a The user account to disable
	 */
	public function accountDisable(Account_model $a) {
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if(!$dn = $this -> dnFromSearch("(".$this -> loginAttribute."=" . $a -> account_login . ")", $ou)) {
			throw new Exception("Skipping account disable, account doesn't seem to exist.");
		}
		
		Account_api::enable($a -> account_id);
		throw new Exception("Account disable is not supported on this service. Account re-marked as enabled");
	}
	
	/**
	 * Enable a user account.
	 *
	 * @param Account_model $a The user account to enable
	 */
	public function accountEnable(Account_model $a) {
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if(!$dn = $this -> dnFromSearch("(".$this -> loginAttribute."=" . $a -> account_login . ")", $ou)) {
			throw new Exception("Skipping account enable, account doesn't seem to exist.");
		}
		
		// Do nothing. These LDAP accounts are always enabled.
		return true;
	}
	
	/**
	 * Relocate a user account
	 *
	 * @param Account_model $a	The account to re-locate
	 * @param Ou_model $o		Unit that the account was formerly located under
	 */
	public function accountRelocate(Account_model $a, Ou_model $old_parent) {
		/* Locate user's current place */
		$ou = $this -> dnFromOu($old_parent -> ou_id);
		if(!$dn = $this -> dnFromSearch("(".$this -> loginAttribute."=".$a -> account_login . ")", $ou)) {
			throw new Exception("Account not found where expected, can't re-locate it!");
		}

		/* Verify new Ou actually exists */
		$superiorSuperior = $this -> dnFromOu($a -> AccountOwner -> Ou -> ou_parent_id);
		if(!$ss = $this -> dnFromSearch("(ou=" . $a -> AccountOwner -> Ou -> ou_name . ")", $superiorSuperior)) {
			outp("\tNew Ou does not exist, creating it now");
			$this -> ouCreate($a -> AccountOwner -> Ou);
		}
		
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
	
	/**
	 * Set the password on an account.
	 *
	 * @param Account_model $a The account to set
	 * @param string p The password to use
	 */
	public function accountPassword(Account_model $a, $p) {
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if(!$dn = $this -> dnFromSearch("(".$this -> loginAttribute."=".$a -> account_login . ")", $ou)) {
			throw new Exception("Account not found where expected, can't set password");
		}
		
		$map = array(
				array('attr' => 'dn',						'value'=> $dn),
				array('attr' => 'changetype',				'value'=> 'modify'),
				array('attr' => 'replace',					'value'=> $this -> passwordAttribute),
				array('attr' => $this -> passwordAttribute,	'value'=> $p)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);	
	}
	
	/**
	 * Search an organizational unit recursively, looking for changes.
	 * The local database will be updated to reflect any changes here.
	 *
	 * @param Ou_model $o The organizational unit to search.
	 */
	public function recursiveSearch(Ou_model $o) {
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
				if(Auth::normaliseName($ou_name) != strtolower($ou_name)) {
					outp("\tNotice: Skipped '$ou_name', would be named differently (".Auth::normaliseName($ou_name).") under Auth");
					continue;
				}
				
				outp("\tFound '$ou_name' here.");
				if(!$ou = Ou_model::get_by_ou_name(Auth::normaliseName($ou_name))) {
					/* Need to create */
					$ou = Ou_api::create($ou_name, $o -> ou_id);
				} else if($ou -> ou_parent_id != $o -> ou_id) {
					/* Wrong place. Move it over before the search (otherwise we'll get errors!) */
					try {
						outp("\tNotice: Found $ou_name here, moving it to where it should be.");
						$this -> OuMove($ou, $o);
					} catch(Exception $e) {
						outp("\tWarning: failed to move $ou_name.");
					}
				}

				/* Recurse */
				outp("\tAdded organizationalUnit $ou_name to search queue", 1);
				ActionQueue_api::submit($this -> service -> service_id, $this -> service -> service_domain, 'recSearch', $ou -> ou_name);
			} elseif(in_array($this -> userObjectClass, $objectClass)) {
				/* Found user account */
				$account_login = $object[$this -> loginAttribute][0];
				outp("\tFound " . $this -> userObjectClass . " $account_login", 1);
				
				/* Crude reconstruction of firstname/lastname */
				if(isset($object['gecos'][0])) {
					$fullname = explode(' ', $object['gecos'][0]);
				} elseif(isset($object['displayName'][0])) {
					$fullname = explode(' ', $object['displayName'][0]);
				} else {
					continue; // Probably computer account 
				}
				$owner_firstname = trim(array_shift($fullname));
				$owner_surname = trim(implode(' ', $fullname));
				
				if($owner_firstname == "") {
					$owner_firstname = $account_login;
				}
				
				if($owner_surname == "") {
					$owner_surname = $account_login;
				}
				
				if(!$account = Account_model::get_by_account_login(Auth::normaliseName($account_login), $this -> service -> service_id, $this -> service -> service_domain)) {
					/* Does not exist on default domain (this LDAP service only supports one domain) */
					$owner = AccountOwner_api::create($o -> ou_id, $owner_firstname, $owner_surname, $account_login, $this -> service -> service_domain, array($this -> service -> service_id));
				} else if ($account -> AccountOwner -> ou_id != $o -> ou_id) {
					outp("\tNotice: Moving account to where it should be.");
					try {
						$this -> accountRelocate($account, $o);
					} catch(Exception $e) {
						outp("\tWarning: failed to move $account_login: " . $e -> getMessage());
					}
				}
			} elseif(in_array($this -> groupObjectClass, $objectClass)) {
				/* Found Group */
				$group_cn = $object['cn'][0];
				if(isset($object['description'][0])) {
					$group_name = $object['description'][0];
				} else {
					$group_name = $group_cn;
				}
				outp("\tFound " . $this -> groupObjectClass . " $group_cn", 1);
				
				if(!$group = UserGroup_model::get_by_group_cn(Auth::normaliseName($group_cn))) {
					try {
						$group = UserGroup_api::create($group_cn, $group_name, $o -> ou_id, $this -> service -> service_domain);
					} catch(Exception $e) {
						outp("\t\tWarning: Couldn't create $group_cn: ".$e -> getMessage());
					}
				} else if ($group -> ou_id != $o -> ou_id) {
					outp("\tNotice: Moving group to where it should be.");
					try {
						$this -> groupMove($group, $o);
					} catch(Exception $e) {
						outp("\tWarning: failed to move $group_cn: " . $e -> getMessage());
					}
				}

				if(isset($object['member'])) {
					foreach($object['member'] as $member) {
						// TODO import group membership
						outp("\t\t$member", 1);
					}
				}
			} else {
				// Ignore unrecognised oddities
			}
		}

		return true;
	}
	
	/**
	 * Create a new group.
	 *
	 * @param Group_model $g The group to create.
	 */
	public function groupCreate(UserGroup_model $g) {
		/* Existence check and startup */
		$ou = $this -> dnFromOu($g -> ou_id);
		if($dn = $this -> dnFromSearch("(cn=" . $g -> group_cn . ")", $ou)) {
			throw new Exception("Skipping group creation, already exists");
		}
		$dn = "cn=" . $g -> group_cn . "," . $ou;
		$description = $g -> group_name;
		
		/* Create group */
		if($this -> dummyGroupMember) {
			$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'add'),
					array('attr' => 'objectClass',	'value'=> $this -> groupObjectClass),
					array('attr' => 'cn',			'value'=> $g -> group_cn),
					array('attr' => 'description',	'value'=> $description),
					array('attr' => 'member',		'value'=> $this -> dummyGroupMember), /* Because every groupOfNames must have a member */
			);
		} else {
			$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'add'),
					array('attr' => 'objectClass',	'value'=> $this -> groupObjectClass),
					array('attr' => 'cn',			'value'=> $g -> group_cn),
					array('attr' => 'description',	'value'=> $description)
			);
		}
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Delete a group
	 *
	 * @param string $group_cn			The 'common name' of the group that was deleted.
	 * @param ListDomain_model $d		The domain to find the group under
	 * @param Ou_model $o				The organziational unit to search for this group in.
	 */
	public function groupDelete($group_cn, ListDomain_model $d, Ou_model $o) {
		/* Locate */
		$ou = $this -> dnFromOu($o -> ou_id);
		if(!$dn = $this -> dnFromSearch("(cn=$group_cn)", $ou)) {
			throw new Exception("Group not found to delete!");
		}
		
		/* Delete */
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'delete')
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);	
	}
	
	/**
	 * Add a user account to a group.
	 *
	 * @param Account_model $a The user account to add
	 * @param Group_model $g The group to add it to
	 */
	public function groupJoin(Account_model $a, UserGroup_model $g) {
		/* Find everything */
		$groupOu = $this -> dnFromOu($g -> ou_id);
		if(!$groupDn = $this -> dnFromSearch("(cn=" . $g -> group_cn . ")", $groupOu)) {
			throw new Exception("Can't find group, not adding to it");
		}

		$AccountOu = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if(!$AccountDn = $this -> dnFromSearch("(".$this -> loginAttribute."=" . $a -> account_login . ")", $AccountOu)) {
			throw new Exception("Can't find user, not adding it to group");
		}

		/* Modify */
		$map = array(
				array('attr' => 'dn',			'value'=> $groupDn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'add',			'value'=> 'member'),
				array('attr' => 'member',		'value'=> $AccountDn)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Remove a user account from a group.
	 *
	 * @param Account_model $a	The user account to remove
	 * @param Group_model $g	The group to remove it from
	 */
	public function groupLeave(Account_model $a, UserGroup_model $g) {
		/* Find everything */
		$groupOu = $this -> dnFromOu($g -> ou_id);
		if(!$groupDn = $this -> dnFromSearch("(cn=" . $g -> group_cn . ")", $groupOu)) {
			throw new Exception("Can't find group, not removing from it");
		}

		$AccountOu = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if(!$AccountDn = $this -> dnFromSearch("(".$this -> loginAttribute."=" . $a -> account_login . ")", $AccountOu)) {
			throw new Exception("Can't find user, not removing it from group");
		}
		
		/* Modify */
		$map = array(
				array('attr' => 'dn',			'value'=> $groupDn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'delete',		'value'=> 'member'),
				array('attr' => 'member',		'value'=> $AccountDn)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Add a group to a group.
	 *
	 * @param Group_model $parent	The parent group
	 * @param Group_model $child	The group to add
	 */
	public function groupAddChild(UserGroup_model $parent, UserGroup_model $child) {
		/* Find everything */
		$parentOu = $this -> dnFromOu($parent -> ou_id);
		if(!$parentDn = $this -> dnFromSearch("(cn=" . $parent -> group_cn . ")", $parentOu)) {
			throw new Exception("Can't find parent group, not adding to it");
		}
		$childOu = $this -> dnFromOu($child -> ou_id);
		if(!$childDn = $this -> dnFromSearch("(cn=" . $child -> group_cn . ")", $childOu)) {
			throw new Exception("Can't find child group, not adding it to group");
		}
		
		/* Modify */
		$map = array(
				array('attr' => 'dn',			'value'=> $parentDn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'add',			'value'=> 'member'),
				array('attr' => 'member',		'value'=> $childDn)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Remove a group from a group.
	 *
	 * @param Group_model $parent The parent group
	 * @param Group_model $child The group to remove
	 */
	public function groupDelChild(UserGroup_model $parent, UserGroup_model $child) {
		/* Find everything */
		$parentOu = $this -> dnFromOu($parent -> ou_id);
		if(!$parentDn = $this -> dnFromSearch("(cn=" . $parent -> group_cn . ")", $parentOu)) {
			throw new Exception("Can't find parent group, not adding to it");
		}
		$childOu = $this -> dnFromOu($child -> ou_id);
		if(!$childDn = $this -> dnFromSearch("(cn=" . $child -> group_cn . ")", $childOu)) {
			throw new Exception("Can't find child group, not adding it to group");
		}

		/* Modify */
		$map = array(
				array('attr' => 'dn',			'value'=> $parentDn),
				array('attr' => 'changetype',	'value'=> 'modify'),
				array('attr' => 'delete',		'value'=> 'member'),
				array('attr' => 'member',		'value'=> $childDn)
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);		
	}
	
	/**
	 * Relocate the group to a different organizational unit
	 *
	 * @param UserGroup_model $g	The group to re-locate
	 * @param Ou_model $old_parent	The organizational unit which it was formerly located under
	 */
	public function groupMove(UserGroup_model $g, Ou_model $old_parent) {
		/* Locate */
		$ou = $this -> dnFromOu($old_parent -> ou_id);
		if(!$dn = $this -> dnFromSearch("(cn=" . $g -> group_cn . ")", $ou)) {
			throw new Exception("Group not found where expected, can't re-locate it!");
		}
		
		/* New details */
		$newsuperior = $this -> dnFromOu($g -> ou_id);
		$newrdn = "cn=" . $g -> group_cn;

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
	
	/**
	 * Change the name of a group
	 *
	 * @param UserGroup_model $g		The group to rename
	 * @param unknown_type $ug_old_cn	The old 'common name' of the group
	 */
	public function groupRename(UserGroup_model $g, $ug_old_cn) {
		$ou = $this -> dnFromOu($g -> ou_id);
		if(!$dn = $this -> dnFromSearch("(cn=" . $ug_old_cn . ")", $ou)) {
			throw new Exception("Group not found where expected, can't rename it!");
		}
		
		/* Fullname */
		$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'modify'),
					array('attr' => 'replace',		'value'=> 'description'),
					array('attr' => 'description',	'value'=> $g -> group_name)
				);
		$ldif = $this -> ldif_generate($map);
		
		if($ug_old_cn != $g -> group_cn) {
			/* Also change 'cn' in directory if we are renaming */
			$newrdn = "cn=" . $g -> group_cn;
			$superior = $this -> dnFromOu($g -> ou_id);
		
			$map = array(
					array('attr' => 'dn',			'value'=> $dn),
					array('attr' => 'changetype',	'value'=> 'modrdn'),
					array('attr' => 'newrdn',		'value'=> $newrdn),
					array('attr' => 'deleteoldrdn',	'value'=> '1'),
					array('attr' => 'newsuperior',	'value'=> $superior)
			);
			$ldif .= $this -> ldif_generate($map);
		}

		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Create a new organizational unit.
	 *
	 * @param Ou_model $o The organizational unit to create
	 */
	public function ouCreate(Ou_model $o) {
		/* Existence check */
		$ou = $this -> dnFromOu($o -> ou_parent_id);
		if($dn = $this -> dnFromSearch("(ou=" . $o -> ou_name . ")", $ou)) {
			throw new Exception("Skipping creating organizationalUnit, already exists.");
		}
		$dn = $this -> dnFromOu($o -> ou_id); // Where it should go.

		/* Add */
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'add'),
				array('attr' => 'objectClass',	'value'=> 'top'),
				array('attr' => 'objectClass',	'value'=> 'organizationalUnit')
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Delete an organizational unit
	 *
	 * @param string $ou_name	The name of the unit to delete.
	 * @param string $d			The domain to find this unit in.
	 * @param Ou_model $o		The parent unit.
	 */
	public function ouDelete($ou_name, ListDomain_model $d, Ou_model $o) {
		/* Locate Ou's current place */
		$ou = $this -> dnFromOu($o -> ou_id);
		if(!$dn = $this -> dnFromSearch("(ou=".$ou_name . ")")) {
			throw new Exception("Unit not found, can't delete it.");
		}

		/* Delete */
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'delete'),
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Move an organizational unit
	 * @param Ou_model $o			The organizational unit to move
	 * @param Ou_model $old_parent	The unit which it was formerly located under.
	 */
	public function ouMove(Ou_model $o, Ou_model $old_parent) {
		/* Locate Ou's current place */
		$ou = $this -> dnFromOu($old_parent -> ou_id);
		if(!$dn = $this -> dnFromSearch("(ou=".$o -> ou_name . ")", $ou)) {
			throw new Exception("Unit not found, can't move it.");
		}
		$newsuperior = $this -> dnFromOu($o -> ou_parent_id);

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
	
	/**
	 * Rename an organizational unit
	 *
	 * @param Ou_model $o
	 * @param Ou_model $name
	 */
	public function ouRename(Ou_model $o, $ou_old_name){
		$ou = $this -> dnFromOu($o -> ou_parent_id);
		if(!$dn = $this -> dnFromSearch("(ou=".$ou_old_name . ")", $ou)) {
			throw new Exception("Unit not found, can't move it.");
		}
		
		$map = array(
				array('attr' => 'dn',			'value'=> $dn),
				array('attr' => 'changetype',	'value'=> 'modrdn'),
				array('attr' => 'newrdn',		'value'=> 'ou=' . $o -> ou_name),
				array('attr' => 'deleteoldrdn',	'value'=> '1'),
				array('attr' => 'newsuperior',	'value'=> $ou),
		);
		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}
	
	/**
	 * Create remote groups/units which are missing, un-track deleted users, and push group membership details out
	 * 
	 * @param Ou_model $o
	 */
	public function syncOu(Ou_model $o) {
		$usergroups = UserGroup_model::list_by_ou_id($o -> ou_id);
		foreach($usergroups as $ug) {
			outp("\tGroup: " . $ug -> group_cn);
			
			try {
				if(!$dn = $this -> dnFromSearch("(cn=" . $g -> group_cn . ")", $ou)) {
					$this -> groupCreate($ug);
					outp("\t\tCreated just now");
				}

				$subUserGroups = UserGroup_api::list_children($ug -> group_id);
				foreach($subUserGroups as $sug) {
					// TODO: Sub-group membership
					outp("\t\tSub-group: " . $sug -> group_cn);
				}
				
				$ownerusergroups = OwnerUserGroup_model::list_by_group_id($ug -> group_id);
				foreach($ownerusergroups as $oug) {
					if($a = $this -> getOwnersAccount($oug -> AccountOwner)) {
						// TODO: User membership
						outp("\t\tUser: " . $a -> account_login . " " . $a -> account_domain);
					}
				}
			} catch(Exception $e) {
				outp("\t\t".$e -> getMessage());
			}
		}

		$accountOwners = AccountOwner_model::list_by_ou_id($o -> ou_id);
		$base = $this -> dnFromOu($o -> ou_id);
		foreach($accountOwners as $ao) {
			if($a = $this -> getOwnersAccount($ao)) {
				outp("\tUser: " . $a -> account_login . " " . $a -> account_domain);
				try {
					if(!$dn = $this -> dnFromSearch("(".$this -> loginAttribute."=" . $a -> account_login . ")", $base)) {
						outp("\t\tAccount has gone missing. Deleting from local database.");
						$a -> delete();
					}
				} catch(Exception $e) {
					outp("\t\t".$e -> getMessage());
				}
			}
		}

		$organizationalunits = Ou_model::list_by_ou_parent_id($o -> ou_id);
		foreach($organizationalunits as $ou) {
			outp("\tUnit: " . $ou -> ou_name);
			try {
				if(!$dn = $this -> dnFromSearch("(ou=" . $ou -> ou_name . ")", $base)) {
					/* Create OU if needed */
					$this -> ouCreate($ou);
					outp("\t\tCreated just now");
				}
				ActionQueue_api::submit($this -> service -> service_id, $this -> service -> service_domain, 'syncOu', $ou -> ou_name);
			} catch(Exception $e) {
				outp("\t\t".$e -> getMessage());
			}
		}

		return true;
	}

	/**
	 * Given an accountOwner, return their account on this service, or false.
	 * 
	 * @param AccountOwner_model $ao
	 */
	private function getOwnersAccount(AccountOwner_model $ao) {
		$ao -> populate_list_Account();
		foreach($ao -> list_Account as $a) {
			if($a -> service_id == $this -> service -> service_id) {
				return $a;
			}
		}
		return false;
	}

	
	/**
	 * Get the dn of the first entry returned from an ldapsearch
	 *
	 * @param string $filter A filter. Example: "(uid=jbloggs)"
	 * @throws Exception
	 */
	protected function dnFromSearch($filter, $base = "") {
		if(!$a = $this -> objectFromSearch($filter, $base)) {
			return false;
		}
		return $a['dn'][0];
	}
	
	protected function objectFromDn($dn) {
		// TODO: Cut first section, filter and find
	}
	
	/**
	 * Get an LDAP object from the directory. Will become annoyed if the filter/base do not come up with a unique result
	 * 
	 * @param string $filter
	 * @param string $base
	 * @throws Exception
	 * @return boolean|Ambigous <multitype:>
	 */
	protected function objectFromSearch($filter, $base = "") {
		if($base == "") {
			$base = $this -> ldap_root;
		}
		$a = $this -> ldapsearch($base, false, $filter);
		if(!isset($a[0]['dn'][0])) {
			return false;
		} else if(isset($a[1]['dn'][0])) {
			foreach($a as $b) {
				if(isset($b['dn'][0])) {
					outp("\tDuplicate for $filter: " . $b['dn'][0]);
				}
			}
			throw new Exception("Duplicate found while searching $filter. Log in to the service and delete one to administer this object!");
		}
		return $a[0];
	}
	
	/**
	 * Get the expected distinguished name of an organizational unit, from the local database
	 * 
	 * @param int $ou_id Organizational unit to look for.
	 * @throws Exception
	 * @return string
	 */
	protected function dnFromOu($ou_id) {
		$ou = Ou_model::get($ou_id);
		if(!$ou) {
			throw new Exception("Organizational unit not found");
		}

		if($ou -> ou_name == "root") {
			return $this -> ldap_root;
		}
		return "ou=" . $ou -> ou_name . "," . $this -> dnFromOu($ou -> ou_parent_id);
	}

	/**
	 * Do a simple LDAP search to verify settings.
	 */
	protected function ldaptest() {
		$this -> ldapsearch_enumerate($this -> ldap_root);
	}

	/**
	 * @param string $base The base of the search, ou=foo,dc=example,dc=com is a good one.
	 * @param boolean $onelevel True to only search one level.
	 * @param string $filter The filter for the search, eg (cn=example).
	 * @throws Exception
	 * @return multitype:multitype: 
	 */
	private function ldapsearch($base, $onelevel = true, $filter = "") {
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
			switch($ret) {
				case 32:
					throw new Exception("ldapsearch failed ($ret): Base $base does not exist!");
				default:
					throw new Exception("ldapsearch failed ($ret): Check your settings and make sure the server is online!");
			}
		}

		return self::parseSearch($lines);
	}

	/**
	 * Enumerate items in an organizational unit
	 * 
	 * @param string $ou the organizational unit to use as the base for the search, in the form ou=...,dc=...,dc=...
	 */
	protected function ldapsearch_enumerate($ou) {
		return $this -> ldapsearch($ou, true);
	}

	protected static function parseSearch($lines) {
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

	protected static function parse_attr($line) {
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

	/**
	 * Submit LDIF code to ldapmodify.
	 * 
	 * @param string $ldif
	 * @throws Exception
	 * @return boolean
	 */
	protected function ldapmodify($ldif) {
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
	protected function ldif_generate($attributes) {
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
	protected function ldif_attr($attr, $value) {
		if(!$this -> ldif_match($attr, 'attr-type-chars')) {
			throw new Exception("Bad attribute: $attr");
		}

		if($attr == "unicodePwd") {
			/* Special Active Directory encoding: http://www.cs.bham.ac.uk/~smp/resources/ad-passwds/ */
			$value = "\"" . $value . "\"";
			$len = strlen($value);
			$newpw = "";
			for ($i = 0; $i < $len; $i++) {
				$newpw .= $value[$i]."\000";
			}
			$newpw = base64_encode($newpw);
			$value = $newpw;
			$attr .= ":";
		} if(!$this -> ldif_match($value, 'safe-string') ||
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
	protected function ldif_match($string, $category) {
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