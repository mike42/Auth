SimonTeacherYL Utility
======================
This is a small utility for automating email distribution lists by using data from SIMON. If you
don't run that software, then you should not enable this utility.

Installation
------------

On the Auth server, you need to install and configure TDS:

        apt-get install php5-odbc tdsodbc

On the Microsoft SQL server, create a _read-only_ user account for Auth to get
its data from. It only needs enough access to read from the following tables:
- StudentClasses
- FileSemesters
- Community
- SubjectClasses
- SubjectClassStaff
- Subjects
	
Set up a ODBC datasource called 'simon'. These settings work for an SQL Server 2008 server:

From odbc.ini:

        [simon]
        Database = YourDatabaseNameHere
        Server = x.x.x.x
        Driver = FreeTDS
        Description = SIMON
        Trace = Yes
        TraceFile = /tmp/sql.log
        ForceTrace = yes
        Port = 1433
        TDS_Version = 8.0

And the FreeTDS driver in odbcinst.ini:

        [FreeTDS]
        Description=MSSQL DB
        Driver=/usr/lib/x86_64-linux-gnu/odbc/libtdsodbc.so
        UsageCount=1

Next, enable the utility by adding an entry to the Util list in config.php:

        'SimonTeacherYL' => 'Automatic mail groups'

And finally, add the login username and password, plus some domain info to a new section in config.php:

        'SimonTeacherYL' => 
        array(
                'user' => 'authusername',
                'pass' => 'verysecretpassword',
                'domain_staff' => 'default',
                'domain_student' => 'student',
                'group_ou_name' => 'root',
                'service_id' => 'ldap1',
                'domain' => array('default' => 'example.com', 'student' => 'student.example.com')
            )
