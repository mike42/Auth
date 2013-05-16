-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 17, 2013 at 10:07 AM
-- Server version: 5.5.30
-- PHP Version: 5.4.4-14

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `auth`
--

--
-- Dumping data for table `Account`
--

INSERT INTO `Account` (`account_id`, `account_login`, `account_domain`, `service_id`, `owner_id`, `account_enabled`) VALUES
(1, 'ebob', 'staff', 'ad', 1, 1),
(2, 'ebob', 'students', 'curricfile01', 1, 1),
(3, 'ebob', 'staff', 'gapps', 1, 1),
(4, 'ebob', 'staff', 'ldap', 1, 1),
(5, 'ebob', 'staff', 'sjcfile01', 1, 1),
(6, 'fbar', 'staff', 'ad', 2, 1),
(7, 'fbar', 'students', 'curricfile01', 2, 1),
(8, 'fbar', 'staff', 'gapps', 2, 1),
(9, 'fbar', 'staff', 'ldap', 2, 1),
(10, 'fbar', 'staff', 'sjcfile01', 2, 1);

--
-- Dumping data for table `AccountOwner`
--

INSERT INTO `AccountOwner` (`owner_id`, `owner_firstname`, `owner_surname`, `ou_id`) VALUES
(1, 'Example', 'Bob', 1),
(2, 'Foo', 'Bar', 1);

--
-- Dumping data for table `ListActionType`
--

INSERT INTO `ListActionType` (`action_type`) VALUES
('acctCreate'),
('acctDelete'),
('acctDisable'),
('acctEnable'),
('acctPasswd'),
('acctRelocate'),
('acctUpdate'),
('grpAddChild'),
('grpCreate'),
('grpDelChild'),
('grpDelete'),
('grpJoin'),
('grpLeave'),
('grpMove'),
('grpRename'),
('ouCreate'),
('ouDelete'),
('ouMove'),
('ouRename'),
('recursiveSea'),
('syncOu');

--
-- Dumping data for table `ListDomain`
--

INSERT INTO `ListDomain` (`domain_id`, `domain_name`, `domain_enabled`) VALUES
('staff', 'Staff', 1),
('students', 'Students', 1);

--
-- Dumping data for table `ListServiceDomain`
--

INSERT INTO `ListServiceDomain` (`service_id`, `domain_id`, `sd_root`, `sd_secondary`) VALUES
('ad', 'staff', '', 0),
('curricfile01', 'staff', '', 1),
('curricfile01', 'students', '', 0),
('gapps', 'staff', '', 0),
('gapps', 'students', '', 0),
('ldap', 'staff', '', 0),
('ldap', 'students', '', 1),
('sjcfile01', 'staff', '', 0);

--
-- Dumping data for table `ListServiceType`
--

INSERT INTO `ListServiceType` (`service_type`) VALUES
('ad'),
('gapps'),
('ldap');

--
-- Dumping data for table `Ou`
--

INSERT INTO `Ou` (`ou_id`, `ou_parent_id`, `ou_name`) VALUES
(1, NULL, 'root'),
(33, 1, 'edrftgyuiop'),
(34, 1, 'giraffe');

--
-- Dumping data for table `Service`
--

INSERT INTO `Service` (`service_id`, `service_name`, `service_enabled`, `service_type`, `service_address`, `service_username`, `service_password`, `service_domain`, `service_pwd_regex`) VALUES
('ad', 'AD (college.network)', 1, 'ad', '', '', '', 'staff', '/^.{1,}$/s'),
('curricfile01', 'AD (curricsjc.local)', 1, 'ad', '', '', '', 'students', '/^.{1,}$/s'),
('gapps', 'Google Apps', 1, 'gapps', '', '', '', 'staff', '/^.{8,}$/s'),
('ldap', 'LDAP', 1, 'ldap', '', '', '', 'staff', '/^.{1,}$/s'),
('sjcfile01', 'AD (panditsjc.local)', 1, 'ad', '', '', '', 'staff', '/^.{1,}$/s');

--
-- Dumping data for table `UserGroup`
--

INSERT INTO `UserGroup` (`group_id`, `group_cn`, `group_name`, `ou_id`, `group_domain`) VALUES
(30, 'allstaffs', 'All Staffers', 34, 'staff'),
(31, 'allstudents', 'All Students', 1, 'students'),
(32, 'allfoo', 'All Foo', 1, 'staff');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
