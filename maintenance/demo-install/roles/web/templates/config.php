<?php
/* Timezone for the ActionQueue */
//date_default_timezone_set('{{ ansible_date_time.tz }}');

/* All other options */
$config = array(
	'Database' =>
		array(
			'name' => '{{ auth_db_name }}',
			'host' => 'localhost',
			'user' => '{{ auth_db_user }}',
			'password' => '{{ auth_db_pass }}'
		),
	'Util' =>
		array(
			'Cleanup'     => 'Directory Cleanup Tools'
		),
	'pidfile' => '/var/run/lock/auth-web.pid',
	'logfile' => '/var/log/auth-web.log',
	'login' =>
		array(
			'url' => 'ldap://localhost',
			'domain' => "{{ ldap_domain_ldif }}",
 			'service_id' => 'ldap1',
			'admin' => array('{{ test_admin_name }}'),
			'assistant' => array('{{ test_assistant_name }}'),
			'assist' =>
				array(
					'domain_id' => 'default',
					'service_id' => 'ldap1'	
				),
			// Leaves data in the database, enables the directory cleanup
			// "Delete all local data" button. These are useful for initial
			// setup, but should be disabled afterward.
			'debug' => 'true'
		),
	'ReceiptPrinter' => array( // Receipt printer, or 0.0.0.0 for no printer
			'ip' => '0.0.0.0',
			'port' => '9100',
			'header' => 'Example',
			'footer' => 'Terms and conditions',
			// Optional - printed at top of receipts if set
			'logo' => dirname(__FILE__) . "/logo.png"
		)
);
