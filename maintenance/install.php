#!/usr/bin/env php
<?php
/**
 * TODO:
 * Check for apache2 packages
 * Prompt for database details
 * Prompt for PID file (in /var/lock or whatever) and log folders (and change permissions for log file folder)
 * Create database account
 * import schema file
 * save config (with very restricted permissions)
 * symlink binaries
 * install authqueue in /etc/init.d
 * Install apache site w/https
 * in www/a/public ln -s /path/to/Auth/site/bg.jpg bg.jpg
 */

/**
 * This script will prompt for some required information, and then install the Auth files on your system.
 *
 * It is designed for Debian 6+. If you are not on this platform, your mileage may vary.
 * 
 * For a very simple symlink-only setup (eg for development), use:
 * 		./install here
 */
$name = "meta-auth";
$user = "meta-auth";
$group = "www-data";
$install = "/usr/share/meta-auth/";
$here = false;
if(isset($argv[1]) && $argv[1] == "here") {
	$here = true;
}

/* Check that we have permissions */
if(posix_getuid() != 0) {
	die("The Auth install script needs to be run as root. Try:\n\tsudo " . $argv[0] . "\n");
}

/* And software check */
if(!extension_loaded('ncurses')) {
	die("The $install install script needs the ncurses PHP extension:\n\tapt-get install php-pear\n\tpecl install ncurses\nDon't forget to add \"extension=ncurses.so\" to php.ini afterward\n");
}

/* Check that install directory doesn't exist */
if(file_exists($install)) {
	die("It looks like $name is already installed at the below location. Aborting.\n\t$install\n");
}

ncurses_init();
if(ncurses_has_colors()) {
	ncurses_start_color();
	ncurses_init_pair(1,NCURSES_COLOR_RED, NCURSES_COLOR_BLACK);
	ncurses_init_pair(2,NCURSES_COLOR_GREEN, NCURSES_COLOR_BLACK);
	ncurses_init_pair(3,NCURSES_COLOR_WHITE, NCURSES_COLOR_BLACK);
}

try {
	/* Create account */
	createAccount($user, $group);

	/* Copy files */
	copyFiles($install, $here);
	
	
} catch(Exception $e) {
	write($e -> getMessage()) . "\n";
}

write("\nInstallation complete. Press any key to exit..\n");

ncurses_getch();
ncurses_end();

function write($str) {
	ncurses_addstr($str);
	if(substr($str,-1) != "\n") {
		/* Space rightward */
		$w = 80 - strlen($str) - 10;
		ncurses_addstr(str_repeat(' ', $w));
	}
	ncurses_refresh();
}

function ok() {
	ncurses_addstr("[");
	ncurses_color_set(2);
	ncurses_addstr("ok");
	ncurses_color_set(3);
	ncurses_addstr("]\n");
	ncurses_refresh();
}

/**
 * Create the user account for Auth to run under
 * 
 * @param string $user The new user account to create.
 * @param string $group An existing group to add it to.
 */
function createAccount($user, $group) {
	$cmd = 'getent passwd | grep ' . escapeshellarg('^' . $user . ":");
	echo $cmd;
	$res = exec($cmd, $outp);
	if($res == "") {
		write("Creating account for $user... ");
		$cmd = "adduser --quiet " .
				"--system " .
				"--ingroup " . escapeshellarg($group) . ' ' .
				"--no-create-home " .
				"--disabled-password " .
				escapeshellarg($user) .  " 2>/dev/null";
		system($cmd);
		ok();
	} else {
		write("User account $user found... no need to create\n");
	}
}

/**
 * Install
 * 
 * @param string $install
 * @param string $here
 */
function copyFiles($install, $here) {
	chdir(dirname(__FILE__) . "/../");
	if($here) {
		write("Linking files ... ");
		$here = dirname(__FILE__) . "/../";
		$there = rtrim($install, "/");
		system("ln -s " . escapeshellarg($here) . ' ' . escapeshellarg($there));
	} else {
		write("Copying files ... ");
		mkdir($install);
		system("cp -R * ".escapeshellarg($install));
	}
	ok();
}
?>
