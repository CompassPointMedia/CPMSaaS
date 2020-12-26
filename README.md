####Overview

Compasspoint SAAS is built on CodeIgniter 4, with some major changes including the "Module Routing Engine" concept, where the database connection and controller are not defined by routes but by one or more modules that inspect the URL.

####Setup

Setting up CompassPoint SAAS is actually very easy.

First, make sure your Nginx or Apache conf file is set to read the various subdomains that you're going to use as accounts.  www.yoursite.com and yoursite.com are considered "root" URLs i.e. no account.  To use the sample account, make sure Nginx or Apache can route nwventures.yoursite.com and also  x0194859gv.yoursite.com.  That last value is the `unique_identifier` and provides less customer information than "nwventures". For future development for the administrative control of CompassPoint SAAS, you should not allow an account named "admin" to be created since admin.yoursite.com will be reserved; it will be used for the admin portal.

Create a master database and user.  Any of the following values can be changed:

```mysql
CREATE DATABASE compasspoint_saas;
GRANT ALL PRIVILEGES ON compasspoint_saas.* TO compasspoint_saas IDENTIFIED BY 'compassPointSAASPassword'; -- obviously change the password
```

The "master" SQL user compasspoint_saas@localhost does not have access to any database besides the main administrative database compasspoint_saas.  That said, the compasspoint_saas database will store the credentials for each account database's SQL user.  The sys_account_password table (you'll create that next) will store the account password, and it would be better to make that table read-only to compasspoint_saas@localhost, add reversible encryption found in the code vs. the database, and probably move the password to an external database or microservice entirely.

Now create a folder named `private` on the same level as your CompassPoint SAAS repository with the following file config.php:


```php
MASTER_DATABASE = 'compasspoint_saas';
$MASTER_USERNAME = 'compasspoint_saas';
$MASTER_HOSTNAME = '127.0.0.1';
$MASTER_PASSWORD = 'compassPointSAASPassword'; # obviously change the password

$public_cnx = ['localhost','data_public','secret','data_public'];
```

`$public_cnx` is optional if you wish to have a database with a user access that contains universal read-only information like country names, state and province and county names, etc.

Now create the system tables in `compasspoint_saas` for CompassPoint SAAS to operate.  They may be found in readme-sql.md under "Database Structure for compasspoint_saas", or in https://compasspointmedia.atlassian.net/wiki/spaces/CPMSAAS/pages/4554753/SQL+and+Database+Structure.

Then insert the seed values for these tables, also found in the Wiki location above, or in readme-sql.md under "Database Seeds for compasspoint_saas".  These include the required role values, and a sample company Northwest Ventures with a fictitious user John Gilmore; delete these rows if you want, but don't delete the `sys_roles` records.

Next, for the user or users above, provide them account access in MYSQL or your database system:

```mysql
GRANT ALL PRIVILEGES ON `cpmsaas_X0194859GV`.* TO ruemex9834@localhost IDENTIFIED BY 'bleefGunJx34'; -- we used the Northwest Ventures account here
```

Where `X0194859GV` is the value in `sys_account.unique_identifier`, and the user and password are from fields `sys_account.system_user` and `sys_account_password.system_password`  respectively.  This username and password is not the user's (John Gilmore) login to CompassPoint SAAS, but rather the SQL database access for that database.  Each database has its own SQL user.

Finally, in the `cpmsaas_X0194859GV` account database, create these tables:

```mysql
-- sys_table
CREATE TABLE `sys_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `table_access` tinyint(3) unsigned DEFAULT '16',
  `literal` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Use actual table_name value',
  `table_group` char(30) NOT NULL DEFAULT 'common',
  `title` char(75) NOT NULL DEFAULT '',
  `description` text,
  `table_name` char(128) NOT NULL DEFAULT '',
  `table_key` char(12) DEFAULT NULL,
  `initial_config` text COMMENT 'PHP array from user creation',
  `css_config_main` text,
  `js_config_main` text,
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creator_id` int(11) DEFAULT NULL,
  `edit_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `editor_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_name` (`table_name`),
  UNIQUE KEY `table_key` (`table_key`),
  KEY `table_group` (`table_group`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- sys_table_config
CREATE TABLE `sys_table_config` (
  `id` int(14) unsigned NOT NULL AUTO_INCREMENT,
  `table_id` int(11) unsigned DEFAULT NULL,
  `data_object` char(40) DEFAULT 'default' COMMENT 'Multiple data objects per table, default=default',
  `user_id` int(11) unsigned DEFAULT NULL COMMENT 'Null value means global value',
  `locked` tinyint(1) unsigned DEFAULT NULL,
  `item_type` char(50) DEFAULT NULL,
  `active` tinyint(1) unsigned DEFAULT '1' COMMENT 'Normally active; a way to turn a feature off',
  `config_id` int(11) unsigned DEFAULT NULL COMMENT 'In case we want hierarchy of some type',
  `node` char(32) DEFAULT NULL,
  `path` char(128) DEFAULT NULL,
  `field_name` char(64) DEFAULT NULL,
  `attribute` char(64) DEFAULT NULL,
  `value` text,
  `comments` text,
  `creator_id` int(11) unsigned DEFAULT NULL COMMENT 'CF Set manual',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'CF Set manual',
  `editor_id` int(11) unsigned DEFAULT NULL COMMENT 'CF Set manual',
  `edit_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'CF Set manual',
  PRIMARY KEY (`id`),
  KEY `table_id` (`table_id`),
  KEY `data_object` (`data_object`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COMMENT='Created by Data::create() December 24th, 2020 at 5:14:23PM';


```

sys_tables is minimally required in each account database in order for it to run.

Your setup is now complete; you should be able to go to nwventures.yoursite.com/ and sign in as `jgilmore:easyForHackers` for account `nwventures` (please, change the password MD5).
