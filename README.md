Auth
====
'Auth' is a scriptable Single Sign On (SSO) solution for organisations which have user accounts in lots of different places. It provides an interface to manage any number of user account databases centrally.

Users can log in via a web portal to reset their password for all services, and view group membership.

The administrative interface lets you modify accounts, groups, and organizational units via the web, and will create a queue of actions which are processed in the background.

Requirements
------------
This code is designed to run on Debian GNU/Linux 6.0 (squeeze) and 7.0 (wheezy). Note that due to changes in libcurl, the current Debian testing branch is not yet supported.

Account databases supported:

- OpenLDAP
- Active Directory (all versions)
- Google Apps, via the Provisioning API.

Dependencies, and which component they are used with (useful for troubleshooting):
- php5-ldap, for logging in via an LDAP or active directory server.
- php5-cli for processing the Action Queue

Optional dependencies:
- phpmyadmin, for managing the database and installing.
- php5-curl, for the Google Apps service
- freetds and sqsh, for plugins which interact with Microsoft SQL server

Installation
------------
The installation steps here cover installing Auth as a standalone LDAP front-end.

Install dependencies, writing down all the information which you will be prompted for.

        apt-get install git apache2 slapd mysql-server phpmyadmin php5-ldap php5-cli php5-curl libapache2-mod-php5

Clone the repo into /usr/share/auth:

        su
        cd /usr/share
        git clone --recursive https://github.com/mike42/Auth auth

Configure apache! You need an ssl virtual host, with AllowOverride All set, mod_rewrite enabled, and its DocumentRoot at /var/www.

These commands will link up your webserver:

        cd /var/www
        ln -s /usr/share/auth/www/a/ a
        ln -s /usr/share/phpmyadmin/ phpmyadmin

The above directories work with the following .htaccess file:

        # Rewrite rules for auth
        RewriteEngine On
        RewriteCond %{DOCUMENT_ROOT}/%{REQUEST_FILENAME} !-f
        RewriteCond %{DOCUMENT_ROOT}/%{REQUEST_FILENAME} !-d

        # Handle stylesheets and scripts
        RewriteRule ^/?admin/css/(.*)$ /a/public/admin/css/$1 [PT,L,QSA]
        RewriteRule ^/?admin/img/(.*)$ /a/public/admin/img/$1 [PT,L,QSA]
        RewriteRule ^/?admin/js/(.*)$ /a/public/admin/js/$1 [PT,L,QSA]

        # Handle everything else
        RewriteRule ^/?admin/(.*)$ /a/admin.php?p=$1 [PT,L,QSA]
        RewriteRule ^/?account/(.*)$ /a/account.php?p=$1 [PT,L,QSA]

If Auth is the only program that runs here, you might want to also make an index.php with this:

        <?php
        header('location: /account/');

Now import the schema into phpmyadmin, from maintenance/schema/auth.sql, and the default data from maintenance/schema/data/defaults.sql

Now cd /usr/share/auth.

Under site/, create a file called bg.jpg, with some company artwork, and config.php. Remembering database and LDAP settings, this is a basic config example.

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
			        'admin' => array('admin')
		        )
        );

Note: Debian 6 Uses /var/lock, not /var/run/lock.

Open the database up and look at the 'service' table. If you are administering LDAP on localhost (this is the default set-up), then correct the domain name and password to make it work. 

To prepare authqueue (a background processs that does all the heavy lifting), you should create its log file, with the right permissions. You could also get super crafty with rotating logs, if you are expecting to generate a lot of data:
	
        touch /var/log/meta-auth.log
        chown www-data /var/log/meta-auth.log

To test the authqueue, run this, and pay close attention to any errors you see:
        
        sudo -u www-data bash
        cd /usr/share/auth/maintenance/bin
        ./authqueue.php -x -v

Caveats
-------
Auth does not know your schema, so by default it uses very basic data structures for groups and users. If you want to take advantage of extra LDAP features, then you should modify ldap_service.php to suit your organization.

Auth will attempt to bring different services "into line" with eachother in terms of group membership and account locations. This process will be annoying, and you should screen-capture your group membership so that you can fix it.

Credits
-------

- The default login background is modified from [this image](http://commons.wikimedia.org/wiki/File:Great_Barrier_Reef_105_(5383117759).jpg) on Wikimedia Commons, CC2.
- The Google API [PHP client](https://code.google.com/p/google-api-php-client/) is included, and is under the Apache License.
