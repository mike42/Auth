<?php
/* Timezone for the ActionQueue */
date_default_timezone_set('UTC');

/* All other options */
$config = array(
	'Database' =>
		array(
			'name' => 'auth_main',
			'host' => 'localhost',
			'user' => 'auth',
			'password' => '...password here...'
		),
	'Util' =>
		array(
			'Cleanup'     => 'Directory Cleanup Tools'
		),
	'pidfile' => '/var/run/lock/meta-auth.pid',
	'logfile' => '/var/log/meta-auth.log',
	'login' =>
		array(
			'url' => 'ldap://localhost',
			'domain' => "dc=example,dc=com",
 			'service_id' => 'ldap1',
			'admin' => array('admin'),
			'assistant' => array(''),
			'assist' =>
				array(
					'domain_id' => 'default',
					'service_id' => 'ldap1'	
				)
		),
	'ReceiptPrinter' => array( // Receipt printer, or 0.0.0.0 for no printer
			'ip' => '0.0.0.0',
			'port' => '9100',
			'header' => 'Example',
			'footer' => 'Terms and conditions'
		)
);
