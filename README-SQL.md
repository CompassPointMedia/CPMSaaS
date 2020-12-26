
##Master Database Structure for compasspoint_saas
Version 1.0

The following tables and fields are needed to run CompassPoint SAAS initially
```mysql
-- Create syntax for TABLE 'sys_account'
CREATE TABLE sys_account (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    name char(100) DEFAULT NULL,
    system_username char(20) DEFAULT NULL,
    identifier char(16) DEFAULT NULL,
    unique_identifier char(32) DEFAULT NULL,
    comments text,
    create_time datetime DEFAULT CURRENT_TIMESTAMP,
    edit_time timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY name (name),
    UNIQUE KEY system_username (system_username),
    UNIQUE KEY identifier (identifier),
    UNIQUE KEY unique_identifier (unique_identifier)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- Create syntax for TABLE 'sys_account_password'
CREATE TABLE sys_account_password (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    account_id int(11) NOT NULL,
    system_password char(64) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY account_id (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Create syntax for TABLE 'sys_account_user_role'
CREATE TABLE sys_account_user_role (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    account_id int(11) unsigned DEFAULT NULL,
    user_id int(11) unsigned NOT NULL,
    role_id int(11) unsigned NOT NULL,
    create_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edit_time timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY account_id (account_id,user_id,role_id)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- Create syntax for TABLE 'sys_login'
CREATE TABLE sys_login (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    account_id int(11) DEFAULT NULL,
    device_id int(11) DEFAULT NULL,
    create_time datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- Create syntax for TABLE 'sys_role'
CREATE TABLE sys_role (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    name char(80) NOT NULL DEFAULT '',
    name_constant char(80) NOT NULL DEFAULT '',
    level int(11) unsigned NOT NULL,
    create_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edit_time timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY name (name),
    UNIQUE KEY name_constant (name_constant)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- Create syntax for TABLE 'sys_user'
CREATE TABLE sys_user (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    email char(80) NOT NULL DEFAULT '',
    first_name char(80) NOT NULL DEFAULT '',
    last_name char(100) NOT NULL DEFAULT '',
    username char(30) NOT NULL DEFAULT '',
    unique_identifier char(32) NOT NULL DEFAULT '',
    password char(100) NOT NULL DEFAULT '',
    password_version tinyint(3) DEFAULT NULL COMMENT '1=md5',
    create_time datetime DEFAULT CURRENT_TIMESTAMP,
    edit_time timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email),
    UNIQUE KEY username (username),
    UNIQUE KEY unique_identifier (unique_identifier)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
```

##Master Database Seeds for compasspoint_saas
Version 1.0

The following values will add the required roles recognized by CompassPoint SAAS, as well as a sample user and account, John Gilmore of Northwest Ventures.

```mysql

/*
* Sequel Pro SQL dump
* Version 4541
*
* http://www.sequelpro.com/
* https://github.com/sequelpro/sequelpro
*
* Host: 127.0.0.1 (MySQL 5.7.32-0ubuntu0.16.04.1)
* Database: compasspoint_saas
* Generation Time: 2020-12-12 09:54:42 +0000
* ************************************************************
*/


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table sys_account
# ------------------------------------------------------------

LOCK TABLES `sys_account` WRITE;
/*!40000 ALTER TABLE `sys_account` DISABLE KEYS */;

INSERT INTO `sys_account` (`id`, `name`, `system_username`, `identifier`, `unique_identifier`, `comments`)
VALUES
	(1,'Northwest Ventures, Inc.','ruemex9834','nwventures','X0194859GV','Account manager, John Gilmore');

/*!40000 ALTER TABLE `sys_account` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table sys_account_password
# ------------------------------------------------------------

LOCK TABLES `sys_account_password` WRITE;
/*!40000 ALTER TABLE `sys_account_password` DISABLE KEYS */;

INSERT INTO `sys_account_password` (`id`, `account_id`, `system_password`)
VALUES
	(1,1,'bleefGunJx34');

/*!40000 ALTER TABLE `sys_account_password` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table sys_account_user_role
# ------------------------------------------------------------

LOCK TABLES `sys_account_user_role` WRITE;
/*!40000 ALTER TABLE `sys_account_user_role` DISABLE KEYS */;

INSERT INTO `sys_account_user_role` (`id`, `account_id`, `user_id`, `role_id`)
VALUES
	(1,1,1,32);

/*!40000 ALTER TABLE `sys_account_user_role` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table sys_role
# ------------------------------------------------------------

LOCK TABLES `sys_role` WRITE;
/*!40000 ALTER TABLE `sys_role` DISABLE KEYS */;

INSERT INTO `sys_role` (`id`, `name`, `name_constant`, `level`)
VALUES
	(1,'God Permissions','PERM_GOD',65356),
	(2,'System Administrator I','PERM_SYSTEM_ADMIN',4096),
	(3,'System User I','PERM_SYSTEM_USER',256),
	(4,'SAAS Administrator I','PERM_SAAS_ADMIN',32),
	(5,'SAAS User I','PERM_SAAS_USER',16),
	(6,'SAAS Guest I','PERM_SAAS_GUEST',4),
	(7,'SAAS Accolyte','PERM_CPM_SAAS_ACCOLYTE',2);

/*!40000 ALTER TABLE `sys_role` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table sys_user
# ------------------------------------------------------------

LOCK TABLES `sys_user` WRITE;
/*!40000 ALTER TABLE `sys_user` DISABLE KEYS */;

-- NOTE: the MD5 value 31ab0529e4491881a10c9d2e93327b14 is for `easyForHackers`; that is the initial password.  Change it..

INSERT INTO `sys_user` (`id`, `email`, `first_name`, `last_name`, `username`, `unique_identifier`, `password`, `password_version`)
VALUES
	(1,'john.gilmore@compasspoint-sw.com','John','Gilmore','jgilmore','102d9fcb8g93','31ab0529e4491881a10c9d2e93327b14',1);

/*!40000 ALTER TABLE `sys_user` ENABLE KEYS */;
UNLOCK TABLES;


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

```

