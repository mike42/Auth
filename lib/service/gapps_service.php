<?php 

require_once(dirname(__FILE__) . "/account_service.php");
require_once(dirname(__FILE__) . "/../misc/Provisioning_Email.php");
require_once(dirname(__FILE__) . "/../vendor/google-api-php-client/src/Google_Client.php");
require_once(dirname(__FILE__) . "/../vendor/google-api-php-client/src/contrib/Google_DirectoryService.php");

class gapps_service extends account_service {
	private $gds;
	private $customerId;
	private $config;

	/**
	 * Used during recSearch. The Google Directory API does not support listing users by orgUnit, so they are all loaded at once and cached.
	 */
	private $recSearch_usercache;

	function __construct(Service_model $service) {
		global $apiConfig;
		$apiConfig['use_objects'] = true;

		$this -> service = $service;
		$this -> config = Auth::getConfig($service -> service_id);

		/* Load key */
		$client = new Google_Client();
		$client -> setApplicationName("Auth https://github.com/mike42/Auth");
		$key = file_get_contents($this -> config['key_file']);
		if(!$key) {
			throw new Exception("Key could not be loaded from file " .$this -> config['key_file']);
		}
			
		/* Load token */
		$token = false;
		$token = @file_get_contents($this -> config['tokenfile']);
		if($token) {
			outp("\tUsing saved login token: " . $this -> config['tokenfile']);
			$client -> setAccessToken($token);
		}
			
		/* Set up auth */
		$gauth = new Google_AssertionCredentials(
				$this -> config['service_account'],
				array('https://www.googleapis.com/auth/admin.directory.group',
						'https://www.googleapis.com/auth/admin.directory.orgunit',
						'https://www.googleapis.com/auth/admin.directory.user'),
				$key);
		$gauth -> sub = $service -> service_username;
		$client -> setAssertionCredentials($gauth);
		$client -> setClientId($this -> config['client_id']);
		$this -> gds = new Google_DirectoryService($client);
			
		/* Look itself up to see if it's logged in */
		try {
			$service_user = $this -> gds -> users -> get($service -> service_username);
			$this -> customerId = $service_user -> getCustomerId();
		} catch(Exception $e) {
			throw new Exception("Failed to retrieve " . $service -> service_username . ": " . $e -> getMessage());
		}
		$this -> recSearch_usercache = false;
			
		/* Token check */
		if (!$client -> getAccessToken()) {
			throw new Exception("Login failed!");
		}
		@file_put_contents($this -> config['tokenfile'], $client -> getAccessToken());
	}

	/**
	 * Make a new user account on this service.
	 *
	 * @param Account_model $a The account to create
	 */
	public function accountCreate(Account_model $a) {
		/* Figure out info */
		$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		$orgUnitPath = $this -> orgUnitPath(false, $a -> AccountOwner -> Ou -> ou_id);

		/* Check that the account does not exist yet */
		$okay = false;
		try {
			$this -> gds -> users -> get($userEmail);
		} catch(Exception $e) {
			// User does not exist (this is good)
			$okay = true;
		}
		if(!$okay) {
			throw new Exception("Account already exists");
		}

		/* Make user */
		try {
			$user = new Google_User();
			$user -> setPrimaryEmail($userEmail);
			$username = new Google_UserName();
			$username -> setGivenName($a -> AccountOwner -> owner_firstname);
			$username -> setFamilyName($a -> AccountOwner -> owner_surname);
			$user -> setName($username);
			$user -> setOrgUnitPath($orgUnitPath);
			$user -> setPassword(sha1($this -> junkPassword()));
			$user -> setHashFunction("SHA-1");
			$user = $this -> gds -> users -> insert($user);
		} catch(Exception $e) {
			throw new Exception("Couldn't create account: " . $e -> getMessage());
		}
		return true;
	}

	/**
	 * Delete a user account from this service.
	 *
	 * @param string $account_login The login of the deleted account
	 * @param ListDomain_model $d The domain name of the deleted account
	 * @param Ou_model $o Unit to search for the login in.
	 */
	public function accountDelete($account_login, ListDomain_model $d, Ou_model $o) {
		$userEmail = $this -> makeEmail($account_login, $d);
			
		try {
			$user = $this -> gds -> users -> get($userEmail);
			$this -> gds -> users -> delete($userEmail);
		} catch(Exception $e) {
			throw new Exception("Couldn't delete account: " . $e -> getMessage());
		}
		return true;
	}

	/**
	 * Update the login and display name for an account.
	 *
	 * @param Account_model $a The account to update
	 * @param string $account_old_login The login to search for (may be different to the one stored currently, if it has been changed)
	 	*/
	public function accountUpdate(Account_model $a, $account_old_login) {
		/* Load user */
		$userEmail = $this -> makeEmail($account_old_login, $a -> ListDomain);
		try {
			$user = $this -> gds -> users -> get($userEmail);
			$doUpdate = false;

			$name = $user -> getName();
			if($a -> AccountOwner -> owner_firstname != $name -> getGivenName() || $a -> AccountOwner -> owner_surname != $name -> getFamilyName) {
				/* Name changes */
				$name -> setGivenName($a -> AccountOwner -> owner_firstname);
				$name -> setFamilyName($a -> AccountOwner -> owner_surname);
				$user -> setName($name);
				$doUpdate = true;
			}

			if($account_old_login != $a -> account_login) {
				/* Login has changed */
				$newUserEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
				$user -> setPrimaryEmail($newUserEmail);
				$doUpdate = true;
			}

			/* Apply changes */
			if(!$doUpdate) {
				throw new Exception("No changes to make");
			}
			$user = $this -> gds -> users -> update($userEmail, $user);
		} catch(Exception $e) {
			throw new Exception("Couldn't update account: " . $e -> getMessage());
		}
		return true;
	}

	/**
	 * Disable a user account.
	 *
	 * @param Account_model $a The user account to disable
	 */
	public function accountDisable(Account_model $a) {
		/* Get user */
		$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		try {
			$user = $this -> gds -> users -> get($userEmail);
			if($user -> getSuspended()) {
				throw new Exception("Account is already suspended");
			}
			$user -> setSuspended(true);
			$this -> gds -> users -> update($userEmail, $user);
		} catch(Exception $e) {
			throw new Exception("Couldn't disable account: " . $e -> getMessage());
		}
		return true;
	}

	/**
	 * Enable a user account.
	 *
	 * @param Account_model $a The user account to enable
	 */
	public function accountEnable(Account_model $a) {
		/* Get user */
		$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		try {
			$user = $this -> gds -> users -> get($userEmail);
			if(!$user -> getSuspended()) {
				throw new Exception("Account is already active");
			}
			$user -> setSuspended(false);
			$this -> gds -> users -> update($userEmail, $user);
		} catch(Exception $e) {
			throw new Exception("Couldn't enable account: " . $e -> getMessage());
		}
		return true;
	}

	/**
	 * Relocate a user account
	 *
	 * @param Account_model $a	The account to re-locate
	 * @param Ou_model $o		Unit that the account was formerly located under
	 */
	public function accountRelocate(Account_model $a, Ou_model $old_parent) {
		/* Get user */
		$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		$orgUnitPath = $this -> orgUnitPath(false, $a -> AccountOwner -> ou_id);
		try {
			$user = $this -> gds -> users -> get($userEmail);
			$user -> setOrgUnitPath($orgUnitPath);
			$this -> gds -> users -> update($userEmail, $user);
		} catch(Exception $e) {
			throw new Exception("Couldn't enable account: " . $e -> getMessage());
		}
		return true;
	}

	/**
	 * Set the password on an account.
	 *
	 * @param Account_model $a The account to set
	 * @param string p The password to use
	 */
	public function accountPassword(Account_model $a, $p) {
		$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		$user = $this -> gds -> users -> get($userEmail);
			
		$user -> setPassword(sha1($p));
		$user -> setHashFunction("SHA-1");
		$this -> gds -> users -> update($userEmail, $user);
		return true;
	}

	/**
	 * Search an organizational unit recursively, looking for changes.
	 * The local database will be updated to reflect any changes here.
	 *
	 * @param Ou_model $o The organizational unit to search.
	 */
	public function recursiveSearch(Ou_model $o) {
		if($o -> ou_name == 'root') {
			$this -> recSearch_usercache = false;
			$unknown = array(); // Unknown logins, to avoid looking them up a zillion times
			
			/* Retrieve groups in pages of 50, and join them together */
			$groupList = $this -> gds -> groups -> listGroups(array("customer" => $this ->customerId));
			$groups = $groupList -> groups;
			while($groupList -> nextPageToken != "") {
				$groupList = $this -> gds -> groups -> listGroups(array("customer" => $this ->customerId, "pageToken" => $groupList -> nextPageToken));
				foreach($groupList -> groups as $g) {
					$groups[] = $g;
				}
			}
			if(!is_array($groups)) {
				$groups = array();
			}

			foreach($groups as $group) {
				$pe = new Provisioning_Email($group -> email);
				$group_cn = $pe -> local;
				try {
					$ug = UserGroup_api::get_by_group_cn($group_cn);
				} catch(Exception $e) {
					/* Doesn't exist, need to create it */
					$domain_id = $this -> getDomainId($pe -> domain);
					$ug = UserGroup_api::create($group_cn, $group -> name, $o -> ou_id, $domain_id);
				}
					
				/* Retrieve all members of the group */
				outp("\tChecking $group_cn");
				$memberList = $this -> gds -> members -> listMembers($group -> email);
				$members = $memberList -> members;
				while($memberList -> nextPageToken != "") {
					$memberList = $this -> gds -> members -> listMembers($group -> email, array("pageToken" => $memberList -> nextPageToken));
					foreach($memberList -> members as $m) {
						$members[] = $m;
					}
				}
				if(!is_array($members)) {
					$members = array();
				}

				/* Check that each group member is also a member in Auth */
				foreach($members as $member) {
					$memberEmail = $member -> email;
					$pe = new Provisioning_Email($memberEmail);
					if($domain_id = $this -> getDomainId($pe -> domain)) { // Ignore outside accounts
						if($member -> type == 'USER') {
							$account_login = $pe -> local;
							if(!isset($unknown[$account_login]) && !$account = Account_model::get_by_account_login($account_login, $this -> service -> service_id, $domain_id)) {
								/* If the account can't be found ... */
								try {
									$user = $this -> gds -> users -> get($memberEmail);
									$userEmail = $user -> getPrimaryEmail();
									if($userEmail != $memberEmail) {
										/* Found as a nickname */
										outp("\t\t Re-adding nickname ($account_login) without nickname ($userEmail). Run again to add to group later.");
										$m = new Google_Member();
										$m -> setEmail($userEmail);
										$this -> gds -> members -> delete($groupEmail, $memberEmail);
										$this -> gds -> members -> insert($groupEmail, $m);
									} else {
										$unknown[$account_login] = true;
										outp("\t\t User unknown ($account_login), skipping. Run again to add to group after the user is found.");
									}
								} catch(Exception $e) {
									outp("\t\t Error working with " . $member -> getmemberId() . ", skipping");
								}
							} else if(isset($unknown[$account_login])) {
								outp("\t\t User still unknown ($account_login), skipping.");
							} else {
								if(!OwnerUserGroup_model::get($account -> owner_id, $ug -> group_id)) {
									// Add owner to group
									AccountOwner_api::addtogroup($account -> owner_id, $ug -> group_id);
								}
							}
						} else if($member -> type == 'GROUP') {
							$subgroup_cn = $pe -> local;
							if(!$subgroup = UserGroup_model::get_by_group_cn($subgroup_cn)) {
								outp("\t\t Group unknown ($subgroup_cn), skipping");
							} else {
								if(!SubUserGroup_model::get($ug -> group_id, $subgroup -> group_id)) {
									// Add sub-group to group
									UserGroup_api::addchild($ug -> group_id, $subgroup -> group_id);
								}
							}
						}
					}
				}
			}
		}


		if($this -> recSearch_usercache === false) {
			outp("\tLoading all users.. This will take some time");
			/* The directory API does not support listing users by orgUnit, so instead, they are all loaded and cached */
			$this -> recSearch_usercache = array();
			$total = 0;
			$userList = $this -> gds -> users -> listUsers(array("customer" => $this -> customerId));
			foreach($userList -> users as $u) {
				$this -> recSearch_usercache[$u -> orgUnitPath][] = $u;
				$total++;
			}
			outp("\t\tLoaded $total users");
			/* Next page.. */
			while($userList -> nextPageToken != "") {
				$userList = $this -> gds -> users -> listUsers(array("customer" => $this -> customerId, "pageToken" => $userList -> nextPageToken));
				foreach($userList -> users as $u) {
					$this -> recSearch_usercache[$u -> orgUnitPath][] = $u;
					$total++;
				}
				outp("\t\tLoaded $total users");
			}
		}

		/* Handle users */
		$orgUnitPath = $this -> orgUnitPath(false, $o -> ou_id);
		$orgUsers = array();
		if(isset($this -> recSearch_usercache[$orgUnitPath])) {
			$orgUsers = $this -> recSearch_usercache[$orgUnitPath];
			unset($this -> recSearch_usercache[$orgUnitPath]);
		}
		foreach($orgUsers as $orgUser) {
			$orgUserEmail = $orgUser -> getPrimaryEmail();
			$pe = new Provisioning_email($orgUserEmail);
			$account_login = $pe -> local;
			$domain_id = $this -> getDomainId($pe -> domain);
			if(!$account = Account_model::get_by_account_login($account_login, $this -> service -> service_id, $domain_id)) {
				/* Account does not exist - find details to make it */
				outp("\tFound account $orgUserEmail");
				$name = $orgUser -> getName();
				$owner_firstname = $name -> getGivenName();
				$owner_surname = $name -> getFamilyName();
				$owner = AccountOwner_api::create($o -> ou_id, $owner_firstname, $owner_surname, $account_login, $domain_id, array($this -> service -> service_id));
			} else if ($account -> AccountOwner -> ou_id != $o -> ou_id) {
				outp("\tNotice: Moving account to where it should be.");
				try {
					$this -> accountRelocate($account, $o);
				} catch(Exception $e) {
					outp("\tWarning: failed to move $account_login: " . $e -> getMessage());
				}
			}
		}

		/* Handle sub-organizations */
		$orgUnitList = $this -> gds -> orgunits -> listOrgunits($this -> customerId, array("orgUnitPath" => $orgUnitPath, "type" => "children"));
		$orgUnits = $orgUnitList -> organizationUnits;
		if(!is_array($orgUnits)) {
			$orgUnits = array();
		}
		
		foreach($orgUnits as $orgUnit) {
			$ou_name = Auth::normaliseName($orgUnit -> name);
			if(!$ou = Ou_model::get_by_ou_name($ou_name)) {
				/* Needs to be created */
				outp("\t$ou_name");
				$ou = Ou_api::create($ou_name, $o -> ou_id);
			} else {
				if($ou -> ou_parent_id != $o -> ou_id) {
					/* Wrong place. Move it over before the search (otherwise we'll get errors!) */
					try {
						outp("\tNotice: Found $ou_name here, moving it to where it should be.");
						$this -> OuMove($ou, $o);
					} catch(Exception $e) {
						outp("\tWarning: failed to move $ou_name.");
					}
				}
			}
			ActionQueue_api::submit($this -> service -> service_id, $this -> service -> service_domain, 'recSearch', $ou -> ou_name);
		}

		return true;
	}

	/**
	 * Create a new group.
	 *
	 * @param Group_model $g The group to create.
	 */
	public function groupCreate(UserGroup_model $g) {
		$groupId = $this -> makeEmail($g -> group_cn, $g -> ListDomain);
			
		/* Check that the group does not exist */
		try { // Make sure that retrieving the group does not work
			$this -> gds -> groups -> get($groupId);
			$okay = false;
		} catch(Exception $e) {
			$okay = true;
		}
		if(!$okay) {
			throw new Exception("Group already exists");
		}

		/* Create the group */
		$group = new Google_Group();
		$group -> setId($groupId);
		$group -> setEmail($groupId);
		$group -> setName($g -> group_name);
		$this -> gds -> groups -> insert($group);
		return true;
	}

	/**
	 * Delete a group
	 *
	 * @param string $group_cn			The 'common name' of the group that was deleted.
	 * @param ListDomain_model $d		The domain to find the group under
	 * @param Ou_model $o				The organziational unit to search for this group in.
	 */
	public function groupDelete($group_cn, ListDomain_model $d, Ou_model $o) {
		$groupId = $this -> makeEmail($group_cn, $d);
		$group = $this -> gds -> groups -> get($groupId);
		$this -> gds -> groups -> delete($groupId);
		return true;
	}

	/**
	 * Add a user account to a group.
	 *
	 * @param Account_model $a The user account to add
	 * @param Group_model $g The group to add it to
	 */
	public function groupJoin(Account_model $a, UserGroup_model $g) {
		$groupEmail = $this -> makeEmail($g -> group_cn, $g -> ListDomain);
		$memberEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
			
		/* Create new member and insert */
		$member = new Google_Member();
		$member -> setEmail($memberEmail);
		$this -> gds -> members -> insert($groupEmail, $member);
		return true;
	}

	/**
	 * Remove a user account from a group.
	 *
	 * @param Account_model $a	The user account to remove
	 * @param Group_model $g	The group to remove it from
	 */
	public function groupLeave(Account_model $a, UserGroup_model $g) {
		$groupEmail = $this -> makeEmail($g -> group_cn, $g -> ListDomain);
		$memberEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
			
		/* Check the membership and then delete it */
		$member = $this -> gds -> members -> get($groupEmail, $memberEmail);
		$this -> gds -> members -> delete($groupEmail, $memberEmail);
		return true;
	}

	/**
	 * Add a group to a group.
	 *
	 * @param Group_model $parent	The parent group
	 * @param Group_model $child	The group to add
	 */
	public function groupAddChild(UserGroup_model $parent, UserGroup_model $child) {
		$groupEmail = $this -> makeEmail($parent -> group_cn, $parent -> ListDomain);
		$memberEmail = $this -> makeEmail($child -> group_cn, $child -> ListDomain);
			
		/* Create new member and insert */
		$member = new Google_Member();
		$member -> setEmail($memberEmail);
		$this -> gds -> members -> insert($groupEmail, $member);
		return true;
	}

	/**
	 * Remove a group from a group.
	 *
	 * @param Group_model $parent The parent group
	 * @param Group_model $child The group to remove
	 */
	public function groupDelChild(UserGroup_model $parent, UserGroup_model $child) {
		$groupEmail = $this -> makeEmail($parent -> group_cn, $parent -> ListDomain);
		$memberEmail = $this -> makeEmail($child -> group_cn, $child -> ListDomain);

		/* Check the membership and then delete it */
		$member = $this -> gds -> members -> get($groupEmail, $memberEmail);
		$this -> gds -> members -> delete($groupEmail, $memberEmail);
		return true;
	}

	/**
	 * Relocate the group to a different organizational unit
	 *
	 * @param UserGroup_model $g	The group to re-locate
	 * @param Ou_model $old_parent	The organizational unit which it was formerly located under
	 */
	public function groupMove(UserGroup_model $g, Ou_model $old_parent) {
		/* Groups are not placed within an orgUnit in Google Apps */
		throw new Exception("Operation not supported");
	}

	/**
	 * Change the name of a group.
	 * Done by creating a new group, copying membership, and deleting the old group.
	 *
	 * @param UserGroup_model $g		The group to rename
	 * @param unknown_type $ug_old_cn	The old 'common name' of the group
	 */
	public function groupRename(UserGroup_model $g, $ug_old_cn) {
		$oldGroupEmail = $this -> makeEmail($ug_old_cn, $g -> ListDomain);
		$groupEmail = $this -> makeEmail($g -> group_cn, $g -> ListDomain);
		$group = $this -> gds -> groups -> get($oldGroupEmail);

		$group -> setName($g -> group_name);
		$group -> setEmail($groupEmail);
		$this -> gds -> groups -> update($oldGroupEmail, $group);
		return true;
	}

	/**
	 * Create a new organizational unit.
	 *
	 * @param Ou_model $o The organizational unit to create
	 */
	public function ouCreate(Ou_model $o) {
		$parentOrgUnitPath = $this -> orgUnitPath(false, $o -> ou_parent_id);
		$okay = false;
		try {
			$orgUnitPath = $this -> orgUnitPath(false, $o -> ou_id, false);
			$orgUnit = $this -> gds -> orgunits -> get($this -> customerId, $orgUnitPath);
		} catch(Exception $e) {
			// Does not exist yet (this is good)
			$okay = true;
		}
		if(!$okay) {
			throw new Exception("OrgUnit already exists at $orgUnitPath");
		}
		
		/* Set up */
		$orgUnit = new Google_OrgUnit();
		$orgUnit -> setName($o -> ou_name);
		$orgUnit -> setParentOrgUnitPath($parentOrgUnitPath);
			
		/* Create */
		$orgUnit = $this -> gds -> orgunits -> insert($this -> customerId, $orgUnit);
		return true;
	}

	/**
	 * Delete an organizational unit
	 *
	 * @param string $ou_name	The name of the unit to delete.
	 * @param string $d			The domain to find this unit in.
	 * @param Ou_model $o		The parent unit.
	 */
	public function ouDelete($ou_name, ListDomain_model $d, Ou_model $o) {
		/* Look up the orgUnit to verify that it exists */
		$orgUnitPath = $this -> orgUnitPath($ou_name, $o -> ou_id, false);
		$orgUnit = $this -> gds -> orgunits -> get($this -> customerId, $orgUnitPath);

		/* Delete the orgUnit */
		$this -> gds -> orgunits -> delete($this -> customerId, $orgUnitPath);
		return true;
	}

	/**
	 * Move an organizational unit
	 * @param Ou_model $o			The organizational unit to move
	 * @param Ou_model $old_parent	The unit which it was formerly located under.
	 */
	public function ouMove(Ou_model $o, Ou_model $old_parent) {
		/* Get unit */
		$oldOrgUnitPath = $this -> orgUnitPath($o -> ou_name, $old_parent -> ou_id, false);
		$orgUnit = $this -> gds -> orgunits -> get($this -> customerId, $oldOrgUnitPath);

		/* Change parent and update */
		$parentOrgUnitPath = $this -> orgUnitPath(false, $o -> ou_parent_id);
		$orgUnit -> setParentOrgUnitPath($parentOrgUnitPath);
		$this -> gds -> orgunits -> update($this -> customerId, $oldOrgUnitPath, $orgUnit);
		return true;
	}

	/**
	 * Rename an organizational unit
	 *
	 * @param Ou_model $o
	 * @param Ou_model $name
	 */
	public function ouRename(Ou_model $o, $ou_old_name) {
		/* Get unit */
		$orgUnitPath = $this -> orgUnitPath($ou_old_name, $o -> ou_parent_id, false);
		$orgUnit = $this -> gds -> orgunits -> get($this -> customerId, $orgUnitPath);

		/* Change name */
		$orgUnit -> setName($o -> ou_name);
		$this -> gds -> orgunits -> update($this -> customerId, $orgUnitPath, $orgUnit);

		return true;
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
				$subUserGroups = UserGroup_api::list_children($ug -> group_id);
				$ownerusergroups = OwnerUserGroup_model::list_by_group_id($ug -> group_id);

				$groupEmail = $this -> makeEmail($ug -> group_cn, $ug -> ListDomain);
				try {
					/* Get group and enumerate group members */
					$group = $this -> gds -> groups -> get($groupEmail);
					$memberList = $this -> gds -> members -> listMembers($groupEmail);
					$members = $memberList -> members;
					while($memberList -> nextPageToken != "") {
						$memberList = $this -> gds -> members -> listMembers($group -> email, array("pageToken" => $memberList -> nextPageToken));
						foreach($memberList -> members as $m) {
							$members[] = $m;
						}
					}
					if(!is_array($members)) {
						$members = array();
					}
					
					/* Make an index */
					$idxAccount = array();
					$idxGroup = array();
					foreach($members as $member) {
						$pe = new Provisioning_Email($member -> email);
						$domain_id = $this -> getDomainId($pe -> domain);
						
						if($domain_id && $member -> type == 'GROUP') {
							$group_cn = $pe -> local;
							$idxGroup[$domain_id][$group_cn] = true;
						} else if($domain_id && $member -> type == 'USER') {
							$account_login = $pe -> local;
							$idxAccount[$domain_id][$account_login] = true;
						}
					}

					/* Add sub-groups */
					foreach($subUserGroups as $sug) {
						if(!isset($idxGroup[$sug -> group_domain][$sug -> group_cn])) {
							try {
								$memberEmail = $this -> makeEmail($sug -> group_cn, $sug -> ListDomain);
								$m = new Google_Member();
								$m -> setEmail($memberEmail);
								$this -> gds -> members -> insert($groupEmail, $m);
								outp("\t\tAdded sub-group: " . $sug -> group_cn);
							} catch(Exception $e) {
								outp("\t\tError adding sub-group " . $sug -> group_cn . ": " . $e -> getMessage());
							}
						} else {
							unset($idxGroup[$sug -> group_domain][$sug -> group_cn]);
						}
					}
					foreach($idxGroup as $domain_id => $list) {
						if(count($list) > 0) {
							outp("\t\tNotice: There are " . count($list) . " $domain_id groups unaccounted for locally. Run search to import them.");
							foreach($list as $m => $t) {
								outp("\t\t\t$m");
							}
						}
					}

					/* Add users */
					foreach($ownerusergroups as $oug) {
						if($a = $this -> getOwnersAccount($oug -> AccountOwner)) {
							if(!isset($idxAccount[$a -> account_domain][$a -> account_login])) {
								try {
									$memberEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
									$m = new Google_Member();
									$m -> setEmail($memberEmail);
									$this -> gds -> members -> insert($groupEmail, $m);
									outp("\t\tAdded user: " . $a -> account_login . " " . $a -> account_domain);
								} catch(Exception $e) {
									outp("\t\tError adding user " . $a -> account_login . ": " . $e -> getMessage());
								}
							} else {
								unset($idxAccount[$a -> account_domain][$a -> account_login]);
							}
						}
					}
					foreach($idxAccount as $domain_id => $list) {
						if(count($list) > 0) {
							outp("\t\tNotice: There are " . count($list) . " $domain_id users unaccounted for locally. Run search to import them:");
							foreach($list as $m => $t) {
								outp("\t\t\t$m");
							}
						}
					}
				} catch(Exception $e) {
					/* Need to create group */
					$this -> groupCreate($ug);
					outp("\t\tCreated just now");

					foreach($subUserGroups as $sug) {
						try {
							$memberEmail = $this -> makeEmail($sug -> group_cn, $sug -> ListDomain);
							$m = new Google_Member();
							$m -> setEmail($memberEmail);
							$this -> gds -> members -> insert($groupEmail, $m);
							outp("\t\tAdded sub-group: " . $sug -> group_cn);
						} catch(Exception $e) {
							outp("\t\tError adding sub-group " . $sug -> group_cn . ": " . $e -> getMessage());
						}
					}

					foreach($ownerusergroups as $oug) {
						if($a = $this -> getOwnersAccount($oug -> AccountOwner)) {
							try {
								$memberEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
								$m = new Google_Member();
								$m -> setEmail($memberEmail);
								$this -> gds -> members -> insert($groupEmail, $m);
								outp("\t\tAdded user: " . $a -> account_login . " " . $a -> account_domain);
							} catch(Exception $e) {
								outp("\t\tError adding user " . $a -> account_login . ": " . $e -> getMessage());
							}
						}
					}
				}
			} catch(Exception $e) {
				outp("\t\t".$e -> getMessage());
			}
		}

		$accountOwners = AccountOwner_model::list_by_ou_id($o -> ou_id);
		$orgUnitPath = $this -> orgUnitPath(false, $o -> ou_id);
		foreach($accountOwners as $ao) {
			if($a = $this -> getOwnersAccount($ao)) {
				outp("\tUser: " . $a -> account_login . " (" . $a -> account_domain . ")");
				$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
				try {
					$user = $this -> gds -> users -> get($userEmail);
					$name = $user -> getName();
					if($name -> getGivenName() != $ao -> owner_firstname || $name -> getFamilyName() != $ao -> owner_surname) {
						outp("\t\tUser firstname or surname mis-match ( '" . $name -> getGivenName() . ", " . $name -> getFamilyName() . "' should be '" . $ao -> owner_surname . ", " . $ao -> owner_firstname . "'). Pushing through an update.");
						$this -> accountUpdate($a, $a -> account_login);
					}
	
					/* Check org unit */
					if($user -> getOrgUnitPath() != $orgUnitPath) {
						/* Incorrect org Unit */
						outp("\t\tFixing orgUnit mis-match: Should be " . $orgUnitPath . " (not " . $user -> getorgUnitPath() . ")");
						$user -> setOrgUnitPath($orgUnitPath);
						$this -> gds -> users -> update($userEmail, $user);
					}
				} catch(Exception $e) {
					/* Note: This block previously deleted the account locally. This is not good, as errors will be thrown for rate
					 * limiting which would untrack many accounts. */
					outp("\t\tWarning: Could not load the account: ".$e -> getMessage());
				}
			}
		}

		$organizationalunits = Ou_model::list_by_ou_parent_id($o -> ou_id);
		foreach($organizationalunits as $ou) {
			outp("\tUnit: " . $ou -> ou_name);
			try {
				$orgUnitPath = $this -> orgUnitPath(false, $ou -> ou_id, false);
				try {
					$orgUnit = $this -> gds -> orgunits -> get($this -> customerId, $orgUnitPath);
				} catch(Exception $e) {
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
	 * Build the path to an organizationUnit based on an ID.
	 *
	 * @param int $ou_id
	 * @throws Exception
	 * @return string path/to/orgUnit, or "/" for the root.
	 */
	private function orgUnitPath($ou_name = false, $ou_parent_id, $leading_slash = true) {
		$ou = Ou_model::get($ou_parent_id);
		$name = array();
		if($ou_name) {
			array_unshift($name, $ou_name);
		}

		while($ou && $ou -> ou_name != "root") {
			array_unshift($name, $ou -> ou_name);
			$ou = Ou_model::get($ou -> ou_parent_id);
		}

		if(count($name) == 0) {
			return "/";
		}
		return ($leading_slash? "/" : "") . implode("/", $name);
	}

	/**
	 * Map 'domains' to action (DNS) domain names and spit out an email address.
	 *
	 * @param string $alias
	 * @param ListDomain_model $domain
	 * @throws Exception
	 * @return string
	 */
	private function makeEmail($alias, ListDomain_model $domain) {
		if(!isset($this -> config['domain'][$domain -> domain_id])) {
			throw new Exception("Couldn't map " . $domain -> domain_id . " to a domain name to make an email address. Check the configuration file!");
		}
		return $alias . "@" . $this -> config['domain'][$domain -> domain_id];
	}

	/**
	 * Convert a domain in the form foo.example.com back to an internal domain_id
	 *
	 * @param string $domain
	 */
	private function getDomainId($domain) {
		return array_search($domain, $this -> config['domain']);
	}
}
?>
