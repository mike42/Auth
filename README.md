# Auth Web [![Build Status](https://travis-ci.org/mike42/Auth.svg?branch=master)](https://travis-ci.org/mike42/Auth)

Auth Web is a system to handle complex user account management setups through a simple web interface.

The aim of this project is to give every user the impression that they have exactly one user account in the organisation, no matter how complex the underlying infrastructure is. It does this by allowing administrators to link user accounts on different systems that are controlled by the same person, so that the person's access can be managed from one place. Each time an action is performed, Auth Web will interact with the relevant systems asynchronously to bring them up to speed.

The use cases for this sort of web application include:

- manage access to systems that don't/can't authenticate centrally
- set a user's password, display name, or group membership on all accounts at once
- set up accounts according to updates to the staff database
- add a web interface so that admins can reset passwords and unlock accounts on the go

The system ships with plugins for:

- LDAP (intended for UNIX or RADIUS accounts)
- Microsoft Active Directory 
- Google Apps, via the Google Data REST API

## Requirements

This code is intended to run on the following platforms:

- The most recent stable release of Debian GNU/Linux
- The most recent LTS release of Ubuntu GNU/Linux

Required software:

- MySQL or MariaDB server
- Apache webserver
- PHP 5.6, with plugins: php5-ldap php5-cli

Optional software:

- phpmyadmin, may be used to perform the database setup through the web
- php5-curl, for the Google Apps service
- php5-odbc and FreeTDS, for plugins which interact with Microsoft SQL Server

## Installation

A standalone example setup is used for testing. Ansible will configure the app and database to manage an empty OpenLDAP domain. For notes on how to install this on a spare Debian-based machine, see the notes under `maintenance/demo-install/README.md`.

## Gotchas

Web Auth does not know your LDAP schema, so by default it uses very basic data structures for groups and users. If you want to take advantage of extra LDAP features, then you should modify `ldap_service.php` to use the features in your schema.

Auth will attempt to align users' group membership and account locations. If it is asked to synchronise two services that are very different, the results are currently quite messy. Ensure that you have done a trial run against a fake system 

## Credits

- The default login background is modified from [this image](http://commons.wikimedia.org/wiki/File:Great_Barrier_Reef_105_%285383117759%29.jpg) on Wikimedia Commons, CC2.
- The Google API [PHP client](https://code.google.com/p/google-api-php-client/) is included, and is under the Apache License.
