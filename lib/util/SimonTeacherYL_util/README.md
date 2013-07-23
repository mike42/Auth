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
                // Service to check for accounts in
                'check'  =>'gapps',
                // Domain to check for accounts in
                'domain' =>'staff',
                'yl_min' => 7,
                'yl_max' => 12,
                'from' => '"Robot" <robot@example.com>'
            ),

The PHP mail() function needs to work correctly for mail delivery. Additionally, you need to make a file in your 'site' folder to use as an email template. The below is a good starting point:

        <?php
        // Email template file
        $message = "<p>Hello ". $person[0] -> Preferred . ",</p>\n";
        $message .= "<p>It looks like you are timetabled for these classes:</p>\n";
        $message .= "<table>\n\t<tr><th>Code</th><th>Name</th><th>Year Level</th>\n";
        print_r($person);
        foreach($person as $class) {
            $message .= "\t<tr><td>" . web::escapeHTML($class -> ClassCode) . "</td><td>" . web::escapeHTML($class -> SubjectDescription) . "</td><td>" . web::escapeHTML($class -> NormalYearLevel? $class -> NormalYearLevel : "(none listed)") . "</td></tr>\n";
        }
        $message .= "</table>\n";

        if(isset($ylMember[$person[0] -> EmailAlias])) {
            $message .= "<p>Based on that, you have been added to the following year-level staff groups for this semester:</p>\n<ul>\n";
            foreach($ylMember[$person[0] -> EmailAlias] as $yl => $true) {
                $message .= "\t<li><a href=\"mailto:".web::escapeHTML(mkgrpname($yl))."@example.com\">".web::escapeHTML(mkgrpname($yl))."@example.com</a></li>\n";
            }
            $message .= "</ul>\n";
        } else {
            $message .= "<p>Because none of your classes have a year-level in the timetable, you have not been put in any staff year-level mail groups this semester.</p>\n";
        }

        $message .= "<p>Please do not respond to this email (I am a robot!)</p>\n";
        $message .= "<p>-- Robot</p>\n";

Save the file as site/SimonTeacherYL-notify.inc
