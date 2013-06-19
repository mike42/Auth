<?php 

require_once(dirname(__FILE__) . "/account_service.php");
require_once(dirname(__FILE__) . "/../vendor/ProvisioningApi/ProvisioningApi.php");

class gapps_service extends account_service {
	private $service;
	private $prov;
	private $config;
	
	function __construct(Service_model $service) {
		$this -> service = $service;
		$this -> config = Auth::getConfig($service -> service_id);

		$token = false;
		$token = @file_get_contents($this -> config['tokenfile']);
		
		if($token) {
			outp("\tUsing saved login token: " . $this -> config['tokenfile']);
		}	
		$this -> prov = new ProvisioningApi($service -> service_username, $service -> service_password, $token);
		@file_put_contents($this -> config['tokenfile'], $this -> prov -> token);
	}
	
	/**
	 * Make a new user account on this service.
	 * 
	 * @param Account_model $a The account to create
	 */
	public function accountCreate(Account_model $a) {		
		/* Figure out info */
		$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		$orgUnitPath = $this -> orgUnitPath($a -> AccountOwner -> ou_id);
		
		/* Make user */
		try {
			$user = $this -> prov -> createUser($userEmail, $a -> AccountOwner -> owner_firstname, $a -> AccountOwner -> owner_surname, sha1($this -> junkPassword()));
		} catch(Exception $e) {
			throw new Exception("Couldn't create account. A group, nickname, or user might already be on that address!");
		}
			
		/* Move into the right Ou */
		if($orgUnitPath != "/") {
			try {
				$orgUser = $this -> prov -> retrieveOrganizationUser($userEmail);
				$orgUser -> setorgUnitPath($orgUnitPath);
				$this -> prov -> updateOrganizationUser($orgUser);
			} catch(Exception $e) {
				/* Has not shown up in directory yet! */
				outp("\t\tFailed to relocate user. Submitting to queue!");
				ActionQueue_api::submit($this -> service -> service_id, $a -> account_domain, 'acctRelocate', $a -> account_login, 'root');
			}
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
		$this -> prov -> deleteUser($userEmail);
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
		$user = $this -> prov -> retrieveUser($userEmail);
		
		/* Apply name changes */
		$doUpdate = false;
		if($a -> AccountOwner -> owner_firstname != $user -> getfirstName()) {
			$user -> setfirstName($a -> AccountOwner -> owner_firstname);
			$doUpdate = true;
		}
		if($a -> AccountOwner -> owner_surname != $user -> getlastName()) {
			$user -> setlastName($a -> AccountOwner -> owner_surname);
			$doUpdate = true;
		}
		if($doUpdate) {
			$this -> prov -> updateUser($user);
		}
		
		/* Login has changed */
		if($account_old_login != $a -> account_login) {
			$newUserEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
			$this -> prov -> renameUser($userEmail, $newUserEmail);
			
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
		$user = $this -> prov -> retrieveUser($userEmail);
		if($user -> getisSuspended()) {
			throw new Exception("Account is already suspended");
		}
		
		/* Suspend user */
		$user -> setisSuspended(true);
		$this -> prov -> updateUser($user);
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
		$user = $this -> prov -> retrieveUser($userEmail);
		if(!$user -> getisSuspended()) {
			throw new Exception("Account is already restored");
		}
		
		/* Suspend user */
		$user -> setisSuspended(false);
		$this -> prov -> updateUser($user);
		return true;
	}

	/**
	 * Relocate a user account
	 * 
	 * @param Account_model $a	The account to re-locate
	 * @param Ou_model $o		Unit that the account was formerly located under
	 */
	public function accountRelocate(Account_model $a, Ou_model $old_parent) {
		/* Figure out what we're doing */
		$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		$orgUnitPath = $this -> orgUnitPath($a -> AccountOwner -> ou_id);
		
		/* Get orgUser, and update orgUnitPath */
		try {
			$orgUser = $this -> prov -> retrieveOrganizationUser($userEmail);
			$orgUser -> setorgUnitPath($orgUnitPath);
			$this -> prov -> updateOrganizationUser($orgUser);
		} catch(Exception $e) {
			return false;
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
		/* Get user */
		$userEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		$user = $this -> prov -> retrieveUser($userEmail);
		
		/* Set password */
		$user -> setpassword(sha1($p));
		$user -> sethashFunction("SHA-1");
		$this -> prov -> updateUser($user);
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
			/* Handle groups */
			$groups = $this -> prov -> retrieveAllGroupsInDomain();
			foreach($groups as $group) {
				$pe = new Provisioning_Email($group -> getgroupId());
				$group_cn = $pe -> local;
				try {
					$ug = UserGroup_api::get_by_group_cn($group_cn);
				} catch(Exception $e) {
					/* Doesn't exist, need to create it */
					$domain_id = $this -> getDomainId($pe -> domain);
					$ug = UserGroup_api::create($group_cn, $group -> getgroupName(), $o -> ou_id, $domain_id);
				}
				
				outp("\tChecking $group_cn");
				$members = $this -> prov -> retrieveMembersOfGroup($group -> getgroupId());
				foreach($members as $member) {
					$me = new Provisioning_Email($member -> getmemberId());
					if($domain_id = $this -> getDomainId($me -> domain)) { // Ignore outside accounts 
						if($member -> getmemberType() == 'User') {
							$account_login = $me -> local;
							if(!$account = Account_model::get_by_account_login($account_login, $this -> service -> service_id, $domain_id)) {
								outp("\t\t User unknown ($account_login), skipping");
							} else {
								if(!OwnerUserGroup_model::get($account -> owner_id, $ug -> group_id)) {
									// Add owner to group
									AccountOwner_api::addtogroup($account -> owner_id, $ug -> group_id);
								}
							}
						} else if($member -> getmemberType() == 'Group') {
							$subgroup_cn = $me -> local;
							if(!$subgroup = UserGroup_model::get_by_group_cn($subgroup_cn)) {
								outp("\t\t Group unknown ($subgroup_cn), skipping");
							} else {
								if(!SubUserGroup_model::get($ug -> group_id, $subgroup -> group_id)) {
									// Add user to group
									UserGroup_api::addchild($ug -> group_id, $subgroup -> group_id);
								}
							}
						}
					}
				}
			}
		}

		/* Handle users */
		$orgUnitPath = $this -> orgUnitPath($o -> ou_id);
		$orgUsers = $this -> prov -> listChildOrganizationUsers($orgUnitPath);
		foreach($orgUsers as $orgUser) {
			$orgUserEmail = $orgUser -> getorgUserEmail();
			$pe = new Provisioning_email($orgUserEmail);
			$account_login = $pe -> local;
			$domain_id = $this -> getDomainId($pe -> domain);
			if(!$account = Account_model::get_by_account_login($account_login, $this -> service -> service_id, $domain_id)) {
				/* Account does not exist - find details to make it */
				outp("\tFound account $orgUserEmail");
				$domainUser = $this -> prov -> retrieveUser($orgUserEmail);
				$owner_firstname = $domainUser -> getfirstName();
				$owner_surname = $domainUser -> getlastName();
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
		$orgUnits = $this -> prov -> listChildOrganizationUnits($orgUnitPath);
		foreach($orgUnits as $orgUnit) {
			$ou_name = Auth::normaliseName($orgUnit -> getname());
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
		$this -> prov -> createGroup($groupId, $g -> group_name, "");
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
		$this -> prov -> deleteGroup($groupId);
		return true;
	}

	/**
	 * Add a user account to a group.
	 * 
	 * @param Account_model $a The user account to add
	 * @param Group_model $g The group to add it to
	 */
	public function groupJoin(Account_model $a, UserGroup_model $g) {
		$memberEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		$groupEmail = $this -> makeEmail($g -> group_cn, $g -> ListDomain);
		$this -> prov -> addMemberToGroup($memberEmail, $groupEmail);
		return true;
	}

	/**
	 * Remove a user account from a group.
	 * 
	 * @param Account_model $a	The user account to remove
	 * @param Group_model $g	The group to remove it from
	 */
	public function groupLeave(Account_model $a, UserGroup_model $g) {
		$memberEmail = $this -> makeEmail($a -> account_login, $a -> ListDomain);
		$groupEmail = $this -> makeEmail($g -> group_cn, $g -> ListDomain);
		$this -> prov -> removeMemberFromGroup($memberEmail, $groupEmail);
		return true;
	}

	/**
	 * Add a group to a group.
	 * 
	 * @param Group_model $parent	The parent group
	 * @param Group_model $child	The group to add
	 */
	public function groupAddChild(UserGroup_model $parent, UserGroup_model $child) {
		$childEmail = $this -> makeEmail($child -> group_cn, $child -> ListDomain);
		$parentEmail = $this -> makeEmail($parent -> group_cn, $parent -> ListDomain);
		$this -> prov -> addMemberToGroup($childEmail, $parentEmail);
		return true;
	}

	/**
	 * Remove a group from a group.
	 * 
	 * @param Group_model $parent The parent group
	 * @param Group_model $child The group to remove
	 */
	public function groupDelChild(UserGroup_model $parent, UserGroup_model $child) {
		$childEmail = $this -> makeEmail($child -> group_cn, $child -> ListDomain);
		$parentEmail = $this -> makeEmail($parent -> group_cn, $parent -> ListDomain);
		$this -> prov -> removeMemberFromGroup($childEmail, $parentEmail);
		return true;
	}

	/**
	 * Relocate the group to a different organizational unit
	 * 
	 * @param UserGroup_model $g	The group to re-locate
	 * @param Ou_model $old_parent	The organizational unit which it was formerly located under
	 */
	public function groupMove(UserGroup_model $g, Ou_model $old_parent) {
		/* Groups do not exist in the context of an Ou in google apps */
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
		if($g -> group_cn != $ug_old_cn) {
			/* This is mad, but the API leaves no other options */
			try {
				outp("\tCreating replacement group..");
				$this -> groupCreate($g);
				
				outp("\tMoving sub-groups..");
				$subgroup = UserGroup_api::list_children($g -> group_id);
				foreach($subgroup as $child) {
					outp("\t\t " . $child -> group_cn . "..");
					$this -> groupAddChild($g, $child);
				}
				$ougs = SubUserGroup_model::list_by_group_id($g -> group_id);
				foreach($oug as $oug) {
					if($account = get_by_service_owner_unique($this -> service -> service_id, $oug -> owner_id)) {
						$this -> groupJoin($account, $g);
					}
				}
				$this -> groupDelete($ug_old_cn, $g -> ListDomain, $g -> Ou);
			} catch(Exception $e) {
				outp("\tProblems encountered. Import may be needed!");
			}
		}
		
		/* Change display name */
		$groupId = $this -> makeEmail($g -> group_cn, $g -> ListDomain);
		try {
			$group = $this -> prov -> retrieveGroup($groupId);
		} catch(Exception $e) {
			throw new Exception("Failed to retrieve group: " . $e -> getMessage());
		}
		
		if($g -> group_name != $group -> getgroupName()) {
			outp("\tChanging group name..");
			$group -> setgroupName($g -> group_name);
			$this -> prov -> updateGroup($group);
		}
		
		return true;
	}

	/**
	 * Create a new organizational unit.
	 * 
	 * @param Ou_model $o The organizational unit to create
	 */
	public function ouCreate(Ou_model $o) {
		$parentOrgUnitPath = $this -> orgUnitPath($o -> ou_parent_id);
		$this -> prov -> createOrganizationUnit($o -> ou_name, $o -> ou_name, $parentOrgUnitPath);
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
		$orgUnitPath = ltrim($this -> orgUnitPath($o -> ou_id) . "/" . urlencode($ou_name), "/");
		$this -> prov -> deleteOrganizationUnit($orgUnitPath);
		return true;
	}

	/**
	 * Move an organizational unit
	 * @param Ou_model $o			The organizational unit to move
	 * @param Ou_model $old_parent	The unit which it was formerly located under.
	 */
	public function ouMove(Ou_model $o, Ou_model $old_parent) {
		/* Get unit */
		$parentOrgUnitPath = $this -> orgUnitPath($old_parent -> ou_id);
		$orgUnitPath = ltrim($parentOrgUnitPath . "/" . urlencode($o -> ou_name), "/");
		$ou = $this -> prov -> retrieveOrganizationUnit($orgUnitPath);
		
		/* Change parent */
		$ou -> setparentOrgUnitPath($this -> orgUnitPath($o -> ou_parent_id));
		$this -> prov -> updateOrganizationUnit($ou);
		return true;
	}

	/**
	 * Rename an organizational unit
	 * 
	 * @param Ou_model $o
	 * @param Ou_model $name
	 */
	public function ouRename(Ou_model $o, $ou_old_name) {
		/* Get unit (at old name) */
		$parentOrgUnitPath = $this -> orgUnitPath($o -> ou_parent_id);
		$orgUnitPath = ltrim($parentOrgUnitPath . "/" . urlencode($ou_old_name), "/");
		$ou = $this -> prov -> retrieveOrganizationUnit($orgUnitPath);
		
		/* Change name */
		$ou -> setname($o -> ou_name);
		$this -> prov -> updateOrganizationUnit($ou);
		return true;
	}
	
	/**
	 * Build the path to an organizationUnit based on an ID.
	 * 
	 * @param int $ou_id
	 * @throws Exception
	 * @return string path/to/orgUnit, or "/" for the root.
	 */
	private function orgUnitPath($ou_id) {
		$ou = Ou_model::get($ou_id);
		if(!$ou) {
			throw new Exception("Organizational unit not found");
		}

		if($ou -> ou_name == "root") {
			return "/";
		}
		return ltrim($this -> orgUnitPath($ou -> ou_parent_id) . "/" . urlencode($ou -> ou_name), "/");
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