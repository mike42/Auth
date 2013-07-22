SimonTeacherYL Utility
==================
This is a small utility for automating user account management via SAS2000. If you
don't run that software, then you should not enable the utility.

If you don't number user accounts according to the 'code' attribute, then you should
also not enable this utility.

Installation
------------

On the Auth server, you need access to the sqsh command, and you need to install and configure FreeTDS to work with your version of Microsoft SQL Server. The instructions on [this blog post](http://le-gall.net/pierrick/blog/index.php/2006/09/06/79-how-to-use-linux-as-a-microsoft-sql-server-client) works for MSSQL 2008.

On the Microsoft SQL server, create a _read-only_ user account for Auth to get
its data from. It only needs enough access to run the following query:

    SELECT
    SubjectClasses.ClassCode, Subjects.Semester1Code, Subjects.Semester2Code,
    Subjects.SubjectCode, Subjects.SubjectDescription, Subjects.NormalYearLevel,
    Community.Preferred, Community.Surname, Community.EmailAddress
    FROM dbo.SubjectClassStaff
    JOIN dbo.SubjectClasses ON SubjectClassStaff.ClassCode = SubjectClasses.ClassCode
    JOIN CISNet3.dbo.Subjects ON Subjects.SubjectCode = SubjectClasses.SubjectCode
    JOIN CISNet3.dbo.Community ON community.UID = SubjectClassStaff.UID
    ORDER BY SubjectClasses.ClassCode, Subjects.SubjectDescription;

Next, enable the utility by adding an entry to the Util list in config.php:

    'SimonTeacherYL' => 'Year-level staff groups'

And finally, add all of the configuration options that the utility will be using:

	'SimonTeacherYL' => 
        array(  'host' => 'hostname',
                'name' => 'databasename',
                'user' => 'authusername',
                'pass' => 'verysecretpassword',
			),

