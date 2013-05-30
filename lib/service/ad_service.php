<?php 

require_once(dirname(__FILE__) . "/ldap_service.php");

class ad_service extends ldap_service {
	private $userAccountControl_enabled;
	private $userAccountControl_disabled;
	private $princialName_Suffix;
	
	function __construct(Service_model $service) {
		parent::__construct($service);
		
		/* This tells the parent to use group (not groupOfNames), and
		 * without a dummy member, which is all that's needed for AD groups */
		$this -> dummyGroupMember = false;
		$this -> groupObjectClass = 'group';
		
		/* See: https://www.google.com/?q=active+directory+useraccountcontrol+codes */
		$this -> userAccountControl_enabled = '66048';
		$this -> userAccountControl_disabled = '66050';
		
		$this -> passwordAttribute = 'unicodePwd';
		
		/* Extract domain as foo.bar.baz.local, used when creating accounts */
		$this -> principalName_Suffix = str_replace('dc=', '', str_replace(',dc=', '.', $service -> service_root));
	}
	
	/**
	 * Make a new user account on this service.
	 * 
	 * @param Account_model $a The account to create
	 */
	public function accountCreate(Account_model $a) {
		/* Check and figure out dn */
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if($dn = $this -> dnFromSearch("(cn=" . $a -> account_login . ")", $ou)) {
			throw new Exception("Skipping account creation, account exists");
		}
		$dn = "cn=" . $a -> account_login . "," . $ou;
	
		/* Create specified account */
		$map = array(
				array('attr' => 'dn',					'value'=> $dn),
				array('attr' => 'changetype',			'value'=> 'add'),
				array('attr' => 'cn',					'value'=> $a -> account_login),
				array('attr' => 'objectClass',			'value'=> 'top'),
				array('attr' => 'objectClass',			'value'=> 'person'),
				array('attr' => 'objectClass',			'value'=> 'organizationalPerson'),
				array('attr' => 'objectClass',			'value'=> 'user'),
				array('attr' => 'givenName',			'value'=> $a -> AccountOwner -> owner_firstname),
				array('attr' => 'sn',					'value'=> $a -> AccountOwner -> owner_surname),
				array('attr' => 'displayName',			'value'=> $a -> AccountOwner -> owner_firstname . ' ' . $a -> AccountOwner -> owner_surname),
				array('attr' => 'sAMAccountName',		'value'=> $a -> account_login),
				array('attr' => 'userPrincipalName',	'value'=> $a -> account_login . '@' . $this -> principalName_Suffix),
				array('attr' => 'userAccountControl',	'value'=> $this -> userAccountControl_enabled),
				array('attr' => 'unicodePwd',			'value'=> $this -> junkPassword())
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
		//TODO
		throw new Exception("Unimplemented");

	}


	/**
	 * Disable a user account.
	 * 
	 * @param Account_model $a The user account to disable
	 */
	public function accountDisable(Account_model $a) {
		/* Check and figure out dn */
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if(!$dn = $this -> dnFromSearch("(cn=" . $a -> account_login . ")", $ou)) {
			throw new Exception("Skipping account creation, account exists");
		}

		$map = array(
			array('attr' => 'dn',					'value'=> $dn),
			array('attr' => 'changetype',			'value'=> 'modify'),
			array('attr' => 'replace',				'value'=> 'userAccountControl'),
			array('attr' => 'userAccountControl',	'value'=> $this -> userAccountControl_disabled),
		);

		$ldif = $this -> ldif_generate($map);
		return $this -> ldapmodify($ldif);
	}

	/**
	 * Enable a user account.
	 * 
	 * @param Account_model $a The user account to enable
	 */
	public function accountEnable(Account_model $a) {
		/* Check and figure out dn */
		$ou = $this -> dnFromOu($a -> AccountOwner -> ou_id);
		if(!$dn = $this -> dnFromSearch("(cn=" . $a -> account_login . ")", $ou)) {
			throw new Exception("Skipping account creation, account exists");
		}

		$map = array(
			array('attr' => 'dn',					'value'=> $dn),
			array('attr' => 'changetype',			'value'=> 'modify'),
			array('attr' => 'replace',				'value'=> 'userAccountControl'),
			array('attr' => 'userAccountControl',	'value'=> $this -> userAccountControl_enabled),
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
		//TODO
		throw new Exception("Unimplemented");

	}
}

?>