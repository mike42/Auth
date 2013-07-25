Example Utility
=========================

This is a simple example for writing plugins (known as 'utilities') for the Auth
system. Plugins are used to implement organisation-specific logic, features not
directly supported, or interfaces to strange and wonderful external databases
that might dictate user accounts or groups.

Normally, this section would include some information about the utility, and why
you might (or might not) want to use it.

Installation
------------

Enable the utility by adding an entry to the Util list in config.php:

    'Example' => 'Example.php1'
