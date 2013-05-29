-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 20, 2013 at 11:33 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `Account`
--

CREATE TABLE IF NOT EXISTS `Account` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_login` varchar(127) CHARACTER SET utf8 NOT NULL,
  `account_domain` varchar(12) NOT NULL,
  `service_id` varchar(12) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `account_enabled` int(1) NOT NULL,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `service_owner_unique` (`service_id`,`owner_id`),
  UNIQUE KEY `account_login` (`account_login`,`service_id`,`account_domain`),
  KEY `owner_id` (`owner_id`),
  KEY `service_id` (`service_id`),
  KEY `account_domain` (`account_domain`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=29 ;

-- --------------------------------------------------------

--
-- Table structure for table `AccountOwner`
--

CREATE TABLE IF NOT EXISTS `AccountOwner` (
  `owner_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_firstname` text NOT NULL,
  `owner_surname` text NOT NULL,
  `ou_id` int(11) NOT NULL,
  PRIMARY KEY (`owner_id`),
  KEY `ou_id` (`ou_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `ActionQueue`
--

CREATE TABLE IF NOT EXISTS `ActionQueue` (
  `aq_id` int(11) NOT NULL AUTO_INCREMENT,
  `aq_attempts` int(11) NOT NULL DEFAULT '0',
  `aq_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `service_id` varchar(12) NOT NULL,
  `domain_id` varchar(12) NOT NULL,
  `action_type` varchar(12) NOT NULL,
  `aq_target` varchar(256) NOT NULL,
  `aq_arg1` text NOT NULL,
  `aq_arg2` text NOT NULL,
  `aq_arg3` text NOT NULL,
  `aq_complete` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`aq_id`),
  KEY `service_id` (`service_id`),
  KEY `domain_id` (`domain_id`),
  KEY `action_type` (`action_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=77 ;

-- --------------------------------------------------------

--
-- Table structure for table `ListActionType`
--

CREATE TABLE IF NOT EXISTS `ListActionType` (
  `action_type` varchar(12) NOT NULL,
  PRIMARY KEY (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ListDomain`
--

CREATE TABLE IF NOT EXISTS `ListDomain` (
  `domain_id` varchar(12) NOT NULL,
  `domain_name` varchar(256) NOT NULL,
  `domain_enabled` int(1) NOT NULL,
  PRIMARY KEY (`domain_id`),
  KEY `domain_enabled` (`domain_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ListServiceDomain`
--

CREATE TABLE IF NOT EXISTS `ListServiceDomain` (
  `service_id` varchar(12) NOT NULL,
  `domain_id` varchar(12) NOT NULL,
  `sd_root` varchar(64) NOT NULL COMMENT 'The root domain name for this domain, in the format it is used on this service',
  `sd_secondary` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`domain_id`),
  KEY `domain_id` (`domain_id`),
  KEY `service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Configure data sources/destinations';

-- --------------------------------------------------------

--
-- Table structure for table `ListServiceType`
--

CREATE TABLE IF NOT EXISTS `ListServiceType` (
  `service_type` varchar(64) NOT NULL,
  PRIMARY KEY (`service_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Ou`
--

CREATE TABLE IF NOT EXISTS `Ou` (
  `ou_id` int(11) NOT NULL AUTO_INCREMENT,
  `ou_parent_id` int(11) DEFAULT NULL,
  `ou_name` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`ou_id`),
  UNIQUE KEY `ou_name` (`ou_name`),
  KEY `ou_parent_id` (`ou_parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Organizational units' AUTO_INCREMENT=39 ;

-- --------------------------------------------------------

--
-- Table structure for table `OwnerUserGroup`
--

CREATE TABLE IF NOT EXISTS `OwnerUserGroup` (
  `owner_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`owner_id`,`group_id`),
  KEY `owner_id` (`owner_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Service`
--

CREATE TABLE IF NOT EXISTS `Service` (
  `service_id` varchar(12) NOT NULL,
  `service_name` text NOT NULL,
  `service_enabled` int(1) NOT NULL,
  `service_type` varchar(64) NOT NULL,
  `service_address` varchar(256) NOT NULL,
  `service_username` varchar(256) NOT NULL,
  `service_password` varchar(256) NOT NULL,
  `service_domain` varchar(12) NOT NULL,
  `service_pwd_regex` varchar(265) NOT NULL DEFAULT '/^.{1,}$/s',
  `service_root` varchar(256) NOT NULL,
  PRIMARY KEY (`service_id`),
  KEY `service_enabled` (`service_enabled`),
  KEY `service_type` (`service_type`),
  KEY `service_domain` (`service_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `SubUserGroup`
--

CREATE TABLE IF NOT EXISTS `SubUserGroup` (
  `parent_group_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`parent_group_id`,`group_id`),
  KEY `parent_group_id` (`parent_group_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `UserGroup`
--

CREATE TABLE IF NOT EXISTS `UserGroup` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_cn` varchar(256) NOT NULL,
  `group_name` varchar(256) NOT NULL,
  `ou_id` int(11) NOT NULL,
  `group_domain` varchar(12) NOT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_cn` (`group_cn`),
  KEY `ou_id` (`ou_id`),
  KEY `group_domain` (`group_domain`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=36 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Account`
--
ALTER TABLE `Account`
  ADD CONSTRAINT `Account_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `Service` (`service_id`),
  ADD CONSTRAINT `Account_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `AccountOwner` (`owner_id`),
  ADD CONSTRAINT `Account_ibfk_3` FOREIGN KEY (`account_domain`) REFERENCES `ListDomain` (`domain_id`);

--
-- Constraints for table `AccountOwner`
--
ALTER TABLE `AccountOwner`
  ADD CONSTRAINT `AccountOwner_ibfk_1` FOREIGN KEY (`ou_id`) REFERENCES `Ou` (`ou_id`);

--
-- Constraints for table `ActionQueue`
--
ALTER TABLE `ActionQueue`
  ADD CONSTRAINT `ActionQueue_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `Service` (`service_id`),
  ADD CONSTRAINT `ActionQueue_ibfk_2` FOREIGN KEY (`domain_id`) REFERENCES `ListDomain` (`domain_id`),
  ADD CONSTRAINT `ActionQueue_ibfk_3` FOREIGN KEY (`action_type`) REFERENCES `ListActionType` (`action_type`);

--
-- Constraints for table `ListServiceDomain`
--
ALTER TABLE `ListServiceDomain`
  ADD CONSTRAINT `ListServiceDomain_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `Service` (`service_id`),
  ADD CONSTRAINT `ListServiceDomain_ibfk_2` FOREIGN KEY (`domain_id`) REFERENCES `ListDomain` (`domain_id`);

--
-- Constraints for table `Ou`
--
ALTER TABLE `Ou`
  ADD CONSTRAINT `Ou_ibfk_1` FOREIGN KEY (`ou_parent_id`) REFERENCES `Ou` (`ou_id`);

--
-- Constraints for table `OwnerUserGroup`
--
ALTER TABLE `OwnerUserGroup`
  ADD CONSTRAINT `OwnerUserGroup_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `AccountOwner` (`owner_id`),
  ADD CONSTRAINT `OwnerUserGroup_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `UserGroup` (`group_id`);

--
-- Constraints for table `Service`
--
ALTER TABLE `Service`
  ADD CONSTRAINT `Service_ibfk_3` FOREIGN KEY (`service_type`) REFERENCES `ListServiceType` (`service_type`),
  ADD CONSTRAINT `Service_ibfk_4` FOREIGN KEY (`service_domain`) REFERENCES `ListDomain` (`domain_id`);

--
-- Constraints for table `SubUserGroup`
--
ALTER TABLE `SubUserGroup`
  ADD CONSTRAINT `SubUserGroup_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `UserGroup` (`group_id`);

--
-- Constraints for table `UserGroup`
--
ALTER TABLE `UserGroup`
  ADD CONSTRAINT `UserGroup_ibfk_1` FOREIGN KEY (`ou_id`) REFERENCES `Ou` (`ou_id`),
  ADD CONSTRAINT `UserGroup_ibfk_2` FOREIGN KEY (`group_domain`) REFERENCES `ListDomain` (`domain_id`);

--
-- Need a root organizationalUnit
--
INSERT INTO `Ou` (`ou_id`, `ou_parent_id`, `ou_name`) VALUES (NULL, NULL, 'root');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
