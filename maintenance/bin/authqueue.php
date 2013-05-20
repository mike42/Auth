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
	outp("Currently " . $count . " items in queue");
	while($next = ActionQueue_model::get_next()) {
		$next -> aq_attempts += 1; // Increment number of attempts
		
		/* Used to exponentially back-off if something is broken */
		$interval = (1 << ($next -> aq_attempts - 1)) - 1;
		$date = time() + $interval;
		
		if(process($next)) {
			try {
				$next -> complete = 1;
				$next -> update();
			} catch(Exception $e) {
				outp("Problem marking item as done; Was it cancelled while it was running?");
			}
		} else {
			try {
				$next -> aq_date = date( 'Y-m-d H:i:s', $date);
				$next -> update();
				outp("Processing failed. Will re-attempt after " . $next -> aq_date);
			} catch(Exception $e) {
				outp("Problem updating item. Was it cancelled while it was running?");
			}
		}
		
		if(isset($switch['-1'])) {
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

function outp($str) {
	global $lf, $switch;
	$str = date(DATE_ATOM) . " " . $str . "\n";
	if(isset($switch['-x'])) {
		echo $str;
	}
	fwrite($lf, $str);
}

function process(ActionQueue_model $aq) {
	global $service;
	outp("Processing " . $aq -> action_type . " (" . $aq -> aq_target . ") on " . $aq -> service_id . "/" . $aq -> domain_id . " - Attempt " . $aq -> aq_attempts);
	
	if(!isset($service[$aq -> service_id])) {
		/* Load up service */
		$class = $aq -> Service -> service_type . "_service";
		outp("\tInitialising " . $aq -> service_id . ".. (an " . $class . ")");
		Auth::loadClass($class);
		$service[$aq -> service_id] = new $class($aq -> Service);
	}
	
	/* Retrieve info and call function in service */
	switch($aq -> action_type) {
		
	}
	//$st = $aq -> Service;
	return false;
}
?>