--
-- Data for table `ListActionType`
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
('recSearch'),
('syncOu');

--
-- Data for table `ListDomain`
--
INSERT INTO `ListDomain` (`domain_id`, `domain_name`, `domain_enabled`) VALUES
('default', 'Default Domain', 1);

--
-- Data for table `ListServiceType`
--

INSERT INTO `ListServiceType` (`service_type`) VALUES
('ldap'), ('gapps'), ('ad');

--
-- Data for table `Ou`
--

INSERT INTO `Ou` (`ou_id`, `ou_parent_id`, `ou_name`) VALUES
(1, NULL, 'root');

--
-- Data for table `Service`
--

INSERT INTO `Service` (`service_id`, `service_name`, `service_enabled`, `service_type`, `service_address`, `service_username`, `service_password`, `service_domain`, `service_pwd_regex`, `service_root`) VALUES
('ldap1', 'LDAP (localhost)', 1, 'ldap', 'ldap://localhost', 'cn=admin,dc=example,dc=com', '', 'default', '/^.{1,}$/s', 'dc=example,dc=com');

--
-- Data for table `ListServiceDomain`
--

INSERT INTO `ListServiceDomain` (`service_id`, `domain_id`, `sd_root`, `sd_secondary`) VALUES
('ldap1', 'default', '', 0);

