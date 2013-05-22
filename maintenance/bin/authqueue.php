#!/usr/bin/env php
<?php
require_once(dirname(__FILE__). "/../../lib/Auth.php");

/* Get lock and open log file*/
try{
	$pidfile = Auth::getConfig('pidfile');
	$logfile = Auth::getConfig('logfile');
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
$count = ActionQueue_api::count();
if($count == 0) {
	outp("Nothing in queue. Stopping.");
} else {
	$services = array();
	outp("Currently " . $count . " items in queue");
	while($next = ActionQueue_model::get_next()) {
		$next -> aq_attempts += 1; // Increment number of attempts

		/* Used to exponentially back-off if something is broken */
		$interval = 0; //(1 << ($next -> aq_attempts - 1)) - 1;
		$date = time() + $interval;

		try {
			if(process($next)) {
				try {
					$next -> aq_complete = 1;
					$next -> update();
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
function outp($str) {
	global $lf, $switch;
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
	
	if(!isset($service[$aq -> service_id])) {
		/* Load up service */
		$class = $aq -> Service -> service_type . "_service";
		outp("\tInitialising " . $aq -> service_id . ".. (an " . $class . ")");
		Auth::loadClass($class);
		try {
			$services[$aq -> service_id] = new $class($aq -> Service);
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
			return $services[$aq -> service_id] -> accountDelete($aq -> aq_target, $aq -> ListDomain);
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
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_arg1)) {
				throw new Exception("acctRelocate: Unit not found");
			}
			return $services[$aq -> service_id] -> accountPassword($a, $o);
			break;
		case 'acctUpdate':
			//TODO
			break;
		case 'grpAddChild':
			//TODO
			break;
		case 'grpCreate':
			//TODO
			break;
		case 'grpDelChild':
			//TODO
			break;
		case 'grpDelete':
			//TODO
			break;
		case 'grpJoin':
			//TODO
			break;
		case 'grpLeave':
			//TODO
			break;
		case 'grpMove':
			//TODO
			break;
		case 'grpRename':
			//TODO
			break;
		case 'ouCreate':
			if(!$o = Ou_model::get_by_ou_name($aq -> aq_target)) {
				throw new Exception("Unit not found");
			}
			return $services[$aq -> service_id] -> ouCreate($o);
		case 'ouDelete':
			return $services[$aq -> service_id] -> ouDelete($aq -> aq_target, $aq -> ListDomain);
		case 'ouMove':
			//TODO
			break;
		case 'ouRename':
			//TODO
			break;
		case 'recursiveSea':
			//TODO
			break;
		case 'syncOu':
			//TODO
			break;
	}
	return false;
}
?>