#!/usr/bin/env php
<?php
require_once(dirname(__FILE__). "/../../vendor/autoload.php");

use Auth\api\ActionQueue_api;
use Auth\api\Ou_api;
use Auth\Auth;
use Auth\model\Account_model;
use Auth\model\ActionQueue_model;
use Auth\model\Ou_model;
use Auth\model\UserGroup_model;

/* Get lock and open log file*/
try{
	$pidfile = Auth::getConfig('pidfile');
	$logfile = Auth::getConfig('logfile');
	$isDebug = Auth::isDebug();
	if(!$pf = fopen($pidfile, "w")) {
		throw new Exception("Couldn't open PID file $pidfile. Check that it is write-able.");
	}
	fwrite($pf, posix_getpid());
	if(!flock($pf, LOCK_EX | LOCK_NB)) {
		throw new Exception("Couldn't get lock on $pidfile. This probably means that ".$argv[0]." is already running.");
	}
	if(!$lf = fopen($logfile, "a")) {
		throw new Exception("Failed to open log file $logfile. Check that it is write-able.");
	}
	/* Check arguments */
	$switch = array();
	foreach($argv as $i => $a) {
		if($i != 0) {
			$switch[$a] = true;
		}
	}
} catch(Exception $e) {
	die("Startup failed: ". $e -> getMessage() . "\n");
}

/* Begin */
outp("Started");
Auth::loadClass("ActionQueue_api");
Auth::loadClass("Ou_api");
$count = ActionQueue_api::count();
if($count == 0) {
	outp("Nothing in queue. Stopping.");
} else {
	$services = array();
	outp("Currently " . $count . " items in queue");
	while($next = ActionQueue_model::get_next()) {
		$next -> aq_attempts += 1; // Increment number of attempts

		/* Used to exponentially back-off if something is broken */
		$interval = (1 << ($next -> aq_attempts - 1)) - 1;
		$date = time() + $interval;

		try {
			if(process($next)) {
				try {
					if($isDebug) {
						$next -> aq_complete = 1;
						$next -> update();
					} else {
						$next -> delete();
					}
				} catch(Exception $e) {
					outp("\tProblem marking item as done; Was it cancelled while it was running?");
				}
			} else {
				try {
					$next -> aq_date = date( 'Y-m-d H:i:s', $date);
					$next -> update();
					outp("\tProcessing error. Will re-attempt after " . $next -> aq_date);
				} catch(Exception $e) {
					outp("\tProblem updating item. Was it cancelled while it was running?");
				}
			}
		} catch(Exception $e) {
			ActionQueue_api::cancel($next -> aq_id);
			outp("\tFailed: " . $e -> getMessage());
		}
		
		if(isset($switch['-1'])) {
			/* Stop after the first item if -1 is set */
			break;
		}
	}

	/* End */
	outp("Stopped");
}

/* Release lock (closing the file does this) and close log file*/
fclose($pf);
fclose($lf);
@unlink($pidfile);

/**
 * Add something to the logfile (and the screen if -x is set).
 * 
 * @param string $str
 */
function outp($str, $verbosity = 1) {
	global $lf, $switch;
	if(!isset($switch['-v']) && $verbosity > 1) {
		/* Skip extended output if -v is not set. */
		return;
	}
	if(isset($switch['-q']) && $verbosity >= 1) {
		/* Skip normal output if -q is set. */
		return;
	}
	
	$str = date(DATE_ATOM) . " " . $str . "\n";
	if(isset($switch['-x'])) {
		echo $str;
	}
	fwrite($lf, $str);
}

/**
 * Process a single item in the queue
 *
 * @param ActionQueue_model $aq the item to process
 * @return boolean True on success, false on failure. An exception will be thrown on permanent failure (something that can't be done), indicating that it should be removed from the queue rather than deferred.
 */
function process(ActionQueue_model $aq) {
	global $services;
	outp("Processing " . $aq -> action_type . " (" . $aq -> aq_target . ") on " . $aq -> service_id . "/" . $aq -> domain_id . " - Attempt " . $aq -> aq_attempts);

	if(!$aq -> Service -> service_enabled == 1) {
		throw new Exception("Will not process because service is disabled");
	}
	
	if(!isset($services[$aq -> service_id])) {
		/* Load up service */
		$className = $aq -> Service -> service_type . "_service";
		outp("\tInitialising " . $aq -> service_id . ".. (an " . $className . ")");
		Auth::loadClass($className);
		try {
		    $fullClassName = "Auth\\service\\${className}";
			$services[$aq -> service_id] = new $fullClassName($aq -> Service);
		} catch(Exception $e) {
			outp("\tInitialisation error: " . $e -> getMessage());
			return false;
		}
	}

	/* Retrieve info and call function in service */
	if(substr($aq -> action_type, 0, 4) == "acct") {
		Auth::loadClass("Account_model");
	}

	switch($aq -> action_type) {
		case 'acctCreate':
			/* Create an account */	
			if(!$a = Account_model::get_by_account_login($aq -> aq_target, $aq -> service_id, $aq -> domain_id)) {
				throw new Exception("acctCreate: Account not found");
			}
			return $services[$aq -> service_id] -> accountCreate($a);
		case 'acctDelete':
			/* Delete account */
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_arg1)) {
				throw new Exception("acctDelete: Unit not found");
			}
			return $services[$aq -> service_id] -> accountDelete($aq -> aq_target, $aq -> ListDomain, $o);
		case 'acctDisable':
			/* Disable account */
			if(!$a = Account_model::get_by_account_login($aq -> aq_target, $aq -> service_id, $aq -> domain_id)) {
				throw new Exception("acctDisable: Account not found");
			}
			return $services[$aq -> service_id] -> accountDisable($a);
		case 'acctEnable':
			/* Enable account */
			if(!$a = Account_model::get_by_account_login($aq -> aq_target, $aq -> service_id, $aq -> domain_id)) {
				throw new Exception("acctEnable: Account not found");
			}
			return $services[$aq -> service_id] -> accountEnable($a);
		case 'acctPasswd':
			/* Enable account */
			if(!$a = Account_model::get_by_account_login($aq -> aq_target, $aq -> service_id, $aq -> domain_id)) {
				throw new Exception("acctPasswd: Account not found");
			}
			return $services[$aq -> service_id] -> accountPassword($a, $aq -> aq_arg1);
		case 'acctRelocate':
			/* Relocate account */
			if(!$a = Account_model::get_by_account_login($aq -> aq_target, $aq -> service_id, $aq -> domain_id)) {
				throw new Exception("acctRelocate: Account not found");
			}
			if(!$old_parent = Ou_model::get_by_ou_name($aq -> aq_arg1)) {
				throw new Exception("acctRelocate: Old parent unit not found");
			}
			return $services[$aq -> service_id] -> accountRelocate($a, $old_parent);
		case 'acctUpdate':
			/* Change user name/login */
			if(!$a = Account_model::get_by_account_login($aq -> aq_target, $aq -> service_id, $aq -> domain_id)) {
				throw new Exception("acctUpdate: Account not found");
			}
			return $services[$aq -> service_id] -> accountUpdate($a, $aq -> aq_arg1);
		case 'grpAddChild':
			/* Add sub-group to group */
			if(!$parent = UserGroup_model::get_by_group_cn($aq -> aq_target)) {
				throw new Exception("grpAddChild: Parent not found");
			}
			if(!$child = UserGroup_model::get_by_group_cn($aq -> aq_arg1)) {
				throw new Exception("grpAddChild: Child not found");
			}
			return $services[$aq -> service_id] -> groupAddChild($parent, $child);
		case 'grpCreate':
			/* Create new group */
			if(!$g = UserGroup_model::get_by_group_cn($aq -> aq_target)) {
				throw new Exception("grpCreate: Group not found");
			}
			return $services[$aq -> service_id] -> groupCreate($g);
		case 'grpDelChild':
			/* Remove sub-group from group */
			if(!$parent = UserGroup_model::get_by_group_cn($aq -> aq_target)) {
				throw new Exception("grpDelChild: Parent not found");
			}
			if(!$child = UserGroup_model::get_by_group_cn($aq -> aq_arg1)) {
				throw new Exception("grpDelChild: Child not found");
			}
			return $services[$aq -> service_id] -> groupDelChild($parent, $child);
		case 'grpDelete':
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_arg1)) {
				throw new Exception("grpDelete: Unit not found");
			}
			return $services[$aq -> service_id] -> groupDelete($aq -> aq_target, $aq -> ListDomain, $o);
		case 'grpJoin':
			/* Add a user to a group */
			if(!$a = Account_model::get_by_account_login($aq -> aq_target, $aq -> service_id, $aq -> domain_id)) {
				throw new Exception("grpJoin: Account not found");
			}
			if(!$g = UserGroup_model::get_by_group_cn($aq -> aq_arg1)) {
				throw new Exception("grpJoin: Group not found");
			}
			return $services[$aq -> service_id] -> groupJoin($a, $g);
		case 'grpLeave':
			/* Remove a user from a group */
			if(!$a = Account_model::get_by_account_login($aq -> aq_target, $aq -> service_id, $aq -> domain_id)) {
				throw new Exception("grpLeave: Account not found");
			}
			if(!$g = UserGroup_model::get_by_group_cn($aq -> aq_arg1)) {
				throw new Exception("grpLeave: Group not found");
			}
			return $services[$aq -> service_id] -> groupLeave($a, $g);
		case 'grpMove':
			/* Move a group */
			if(!$g = UserGroup_model::get_by_group_cn($aq -> aq_target)) {
				throw new Exception("grpMove: Group not found");
			}
			if(!$old_parent = Ou_model::get_by_ou_name($aq -> aq_arg1)) {
				throw new Exception("grpMove: Old parent unit not found");
			}
			return $services[$aq -> service_id] -> groupMove($g, $old_parent);
		case 'grpRename':
			/* Rename a group */
			if(!$g = UserGroup_model::get_by_group_cn($aq -> aq_target)) {
				throw new Exception("grpRename: Group not found");
			}
			return $services[$aq -> service_id] -> groupRename($g, $aq -> aq_arg1);
		case 'ouCreate':
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_target)) {
				throw new Exception("ouCreate: Unit not found");
			}
			return $services[$aq -> service_id] -> ouCreate($o);
		case 'ouDelete':
			if(!$parent = Ou_model::get_by_ou_name($aq -> aq_arg1)) {
				throw new Exception("ouDelete: Parent unit not found");
			}
			return $services[$aq -> service_id] -> ouDelete($aq -> aq_target, $aq -> ListDomain, $parent);
		case 'ouMove':
			/* Move OU to a new parent */
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_target)) {
				throw new Exception("ouMove: Unit not found");
			}
			if(!$oldparent = Ou_model::get_by_ou_name($aq -> aq_arg1)) {
				throw new Exception("ouMove: Old parent unit not found");
			}
			return $services[$aq -> service_id] -> ouMove($o, $oldparent);
		case 'ouRename':
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_target)) {
				throw new Exception("ouRename: Unit not found");
			}
			return $services[$aq -> service_id] -> ouRename($o, $aq -> aq_arg1);
		case 'recSearch':
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_target)) {
				throw new Exception("recursiveSearch: Unit not found");
			}
			return $services[$aq -> service_id] -> recursiveSearch($o);
		case 'syncOu':
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_target)) {
				throw new Exception("syncOu: Unit not found");
			}
			return $services[$aq -> service_id] -> syncOu($o);
	}
	return false;
}
?>