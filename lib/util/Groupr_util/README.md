Group Reset Utility
==================
This utility is for the batch resetting of user passwords. It resets the passwords of every member of a group and outputs the user name and password in an excel/csv compatible format for copying. 

The reset will ONLY reset the accounts of the members in the main group as defined and not those of any subgroups.


Installation
------------

To enable the utility add an entry to the Util list in config.php:

        'Util' =>
            array(
                // List of other utilities ...
                'Groupr' => 'GroupReset'
            )

And finally, add all of the configuration options that the utility will be using:

        'Groupr' => 
            array(  'service_id' => 'ldap')

This ensures that only users who have accounts on this service can have their passwords reset using this utility.
