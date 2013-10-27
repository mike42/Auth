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
 			'service_id' => 'ldap',
			'admin' => array('admin')
		)
);
