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
-- Create syntax for TABLE 'sys_data_object'
CREATE TABLE `sys_data_object` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned DEFAULT NULL,
  `title` char(75) NOT NULL DEFAULT '',
  `description` text,
  `table_name` char(128) NOT NULL DEFAULT '',
  `table_key` char(12) DEFAULT NULL,
  `table_label` char(128) DEFAULT NULL COMMENT 'e.g. employee-payroll-category',
  `table_access` tinyint(3) unsigned DEFAULT '16',
  `enable_auditing` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `primary_key_reserved` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `control_fields_system_managed` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `version` char(20) DEFAULT 'v0.1 prototype',
  `initial_config` text COMMENT 'PHP array from user creation',
  `css_config_main` text,
  `create_method` char(30) DEFAULT 'unspecified',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creator_id` int(11) DEFAULT NULL,
  `edit_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `editor_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_name` (`table_name`),
  UNIQUE KEY `table_key` (`table_key`),
  UNIQUE KEY `table_label` (`table_label`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Create syntax for TABLE 'sys_data_object_group'
CREATE TABLE `sys_data_object_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(30) NOT NULL DEFAULT '',
  `identifier` char(30) NOT NULL DEFAULT '',
  `description` text,
  `css_config_main` text,
  `create_method` char(30) DEFAULT 'unspecified',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `creator_id` int(11) unsigned DEFAULT NULL,
  `edit_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `editor_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- sys_data_object_config
CREATE TABLE `sys_data_object_config` (
  `id` int(14) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(11) unsigned DEFAULT NULL COMMENT 'polymorphic on multiple tables',
  `object_type` enum('sys_data_object','sys_data_group','sys_data_group_xref') NOT NULL DEFAULT 'sys_data_object' COMMENT 'polymorphic on multiple tables',
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sys_data_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `table_id` int(11) unsigned DEFAULT NULL,
  `group_key` char(16) DEFAULT NULL,
  `group_label` char(64) DEFAULT NULL,
  `default` tinyint(1) unsigned DEFAULT '1',
  `data_group_id` int(11) unsigned DEFAULT NULL,
  `title` char(128) NOT NULL DEFAULT '',
  `description` text,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `creator_id` int(11) unsigned DEFAULT NULL,
  `edit_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `editor_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_key` (`group_key`),
  UNIQUE KEY `group_label` (`group_label`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sys_data_group_xref` (
  `data_group_id` int(11) unsigned NOT NULL,
  `child_object_type` enum('sys_data_object','sys_data_group','sys_data_group_xref') NOT NULL DEFAULT 'sys_data_object',
  `child_object_id` int(11) unsigned NOT NULL,
  `child_object_relationship` enum('user-defined','root table','dependent table','to be defined') NOT NULL DEFAULT 'to be defined',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `creator_id` int(11) unsigned DEFAULT NULL,
  `edit_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `editor_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`data_group_id`,`child_object_type`,`child_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sys_changelog` (
  `id` int(15) unsigned NOT NULL AUTO_INCREMENT,
  `object_name` char(100) DEFAULT NULL COMMENT 'Reference table',
  `object_key` int(11) DEFAULT NULL COMMENT 'Reference table id',
  `data_source` enum('system','user') DEFAULT NULL COMMENT 'Source of entry (system or human)',
  `type` enum('value change','comment','insert record','delete record') DEFAULT NULL,
  `creator` char(50) DEFAULT NULL,
  `affected_element` char(128) DEFAULT NULL COMMENT 'Specifies field(s) affected',
  `change_from` text,
  `change_to` text,
  `comment` text,
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `edit_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Auto-update; do not touch for normal non-system updates',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `object_key` (`object_key`),
  KEY `create_time` (`create_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

```

sys_data_object is minimally required in each account database in order for it to run.

Your setup is now complete; you should be able to go to nwventures.yoursite.com/ and sign in as `jgilmore:easyForHackers` for account `nwventures` (please, change the password MD5).

## 2019-09-11: hold notes
CI4 is radically different enough to represent a refactor of packages and my Data class to the point that I decided to hold off for now.
It also has the spectre of being slow, which is the whole reason for CI in the first place
The routing holds promise however, and the namespacing seems to be more flexible; the documentation says the system will even make "educated guesses"
about the proper class to load.


# CodeIgniter 4 Development

[![Build Status](https://github.com/codeigniter4/CodeIgniter4/workflows/PHPUnit/badge.svg)](https://github.com/codeigniter4/CodeIgniter4/actions?query=workflow%3A%22PHPUnit%22)
[![Coverage Status](https://coveralls.io/repos/github/codeigniter4/CodeIgniter4/badge.svg?branch=develop)](https://coveralls.io/github/codeigniter4/CodeIgniter4?branch=develop)
[![Downloads](https://poser.pugx.org/codeigniter4/framework/downloads)](https://packagist.org/packages/codeigniter4/framework)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/codeigniter4/CodeIgniter4)](https://packagist.org/packages/codeigniter4/framework)
[![GitHub stars](https://img.shields.io/github/stars/codeigniter4/CodeIgniter4)](https://packagist.org/packages/codeigniter4/framework)
[![GitHub license](https://img.shields.io/github/license/codeigniter4/CodeIgniter4)](https://github.com/codeigniter4/CodeIgniter4/blob/develop/LICENSE)
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/codeigniter4/CodeIgniter4/pulls)
<br>

## What is CodeIgniter?

CodeIgniter is a PHP full-stack web framework that is light, fast, flexible and secure.
More information can be found at the [official site](http://codeigniter.com).

This repository holds the source code for CodeIgniter 4 only.
Version 4 is a complete rewrite to bring the quality and the code into a more modern version,
while still keeping as many of the things intact that has made people love the framework over the years.

More information about the plans for version 4 can be found in [the announcement](http://forum.codeigniter.com/thread-62615.html) on the forums.

### Documentation

The [User Guide](https://codeigniter4.github.io/userguide/) is the primary documentation for CodeIgniter 4.

The current **in-progress** User Guide can be found [here](https://codeigniter4.github.io/CodeIgniter4/).
As with the rest of the framework, it is a work in progress, and will see changes over time to structure, explanations, etc.

You might also be interested in the [API documentation](https://codeigniter4.github.io/api/) for the framework components.

## Important Change with index.php

index.php is no longer in the root of the project! It has been moved inside the *public* folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's *public* folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter *public/...*, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

CodeIgniter is developed completely on a volunteer basis. As such, please give up to 7 days
for your issues to be reviewed. If you haven't heard from one of the team in that time period,
feel free to leave a comment on the issue so that it gets brought back to our attention.

We use Github issues to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

If you raise an issue here that pertains to support or a feature request, it will
be closed! If you are not sure if you have found a bug, raise a thread on the forum first -
someone else may have encountered the same thing.

Before raising a new Github issue, please check that your bug hasn't already
been reported or fixed.

We use pull requests (PRs) for CONTRIBUTIONS to the repository.
We are looking for contributions that address one of the reported bugs or
approved work packages.

Do not use a PR as a form of feature request.
Unsolicited contributions will only be considered if they fit nicely
into the framework roadmap.
Remember that some components that were part of CodeIgniter 3 are being moved
to optional packages, with their own repository.

## Contributing

We **are** accepting contributions from the community!

We will try to manage the process somewhat, by adding a ["help wanted" label](https://github.com/codeigniter4/CodeIgniter4/labels/help%20wanted) to those that we are
specifically interested in at any point in time. Join the discussion for those issues and let us know
if you want to take the lead on one of them.

At this time, we are not looking for out-of-scope contributions, only those that would be considered part of our controlled evolution!

Please read the [*Contributing to CodeIgniter*](https://github.com/codeigniter4/CodeIgniter4/blob/develop/CONTRIBUTING.md) section in the user guide.

## Server Requirements

PHP version 7.3 or higher is required, with the following extensions installed:


- [intl](http://php.net/manual/en/intl.requirements.php)
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- xml (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php)

## Running CodeIgniter Tests

Information on running the CodeIgniter test suite can be found in the [README.md](tests/README.md) file in the tests directory.
