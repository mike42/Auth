SasStudent Utility
==================
This is a small utility for automating user account management via SAS2000. If you
don't run that software, then you should not enable the utility.

If you don't number user accounts according to the 'code' attribute, then you should
also not enable this utility.

Installation
------------

On the Auth server, you need access to the sqsh command, and you need to install and configure FreeTDS to work with your version of Microsoft SQL Server. [This blog post](http://le-gall.net/pierrick/blog/index.php/2006/09/06/79-how-to-use-linux-as-a-microsoft-sql-server-client) works for MSSQL 2008.

On the Microsoft SQL server, create a /read-only/ user account for Auth to get
its data from. Give it access to a simple view of student data. The view I use is:

    SELECT     Code, FirstName, LastName, PreferredName, Year, Class, ID
    FROM         dbo.Student
    WHERE     (PreEnrolment = 'N')

Next, enable the utility by adding an entry to the Util list in config.php:

    'SasStudent' => 'SAS Student'

And finally, add all of the configuration options that the utility will be using:

    'SasStudent' => 
        array(  'host' => 'hostname',
                'name' => 'databasename',
                'user' => 'authusername',
                'pass' => 'verysecretpassword',
				'view' => 'dbo.authStudentView',
                // Service to check for accounts in
                'check'  =>'ldap',
                // Domain to check for accounts in
                'domain' =>'(the domain where student accounts go)',
                // Services to create accounts on
                'create' => array('ldap', 'something', 'something-else')
            )
