<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
	/**
	 * The directory that holds the Migrations
	 * and Seeds directories.
	 *
	 * @var string
	 */
	public $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

	/**
	 * Lets you choose which connection group to
	 * use if no other is specified.
	 *
	 * @var string
	 */
	public $defaultGroup = 'default';

	/**
	 * The default database connection.
	 *
	 * @var array
	 */
	public $default = [
		'DSN'      => '',
		'hostname' => 'localhost',
		'username' => '',
		'password' => '',
		'database' => '',
		'DBDriver' => 'MySQLi',
		'DBPrefix' => '',
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'charset'  => 'utf8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 3306,
	];

    /**
     * The MASTER database connection.
     * For least possible amazement and maximum security against screw-ups, we call the god-connection $master
     *
     * @var array
     */
    public $master = [
        'DSN'      => '',
        'hostname' => 'localhost',
        'username' => '',
        'password' => '',
        'database' => '',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => (ENVIRONMENT !== 'production'),
        'charset'  => 'utf8',
        'DBCollat' => 'utf8_general_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 3306,
    ];

    /**
     * The list of account connections for the current user; they may vary by account (2020-11-29 database and account are
     * analogous at this point), and they may not be the account's MYSQL system_username and system_password listed in the
     * accounts database.  This variable need to be accessible to be added to.
     *
     * @var array $accounts
     */
	public $accounts = [];


	/**
	 * This database connection is used when
	 * running PHPUnit database tests.
	 *
	 * @var array
	 */
	public $tests = [
		'DSN'      => '',
		'hostname' => '127.0.0.1',
		'username' => '',
		'password' => '',
		'database' => ':memory:',
		'DBDriver' => 'SQLite3',
		'DBPrefix' => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'charset'  => 'utf8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 3306,
	];

	//--------------------------------------------------------------------

	public function __construct()
	{

        // Get settings outside of CI repository
        if(! file_exists(ROOTPATH . '../private/config.php'))
        {
            // Error T01
            throw new \App\Exceptions\GeneralFault(1);
        }
        require(ROOTPATH . '../private/config.php');

        // Set values from file
        $this->master['hostname'] = $MASTER_HOSTNAME;
        $this->master['username'] = $MASTER_USERNAME;
        $this->master['password'] = $MASTER_PASSWORD;
        $this->master['database'] = $MASTER_DATABASE;


        parent::__construct();


        /**
         * Juliet CMS imported credentials, see ../private/config.php
         * $active_group was used in CodeIgniter 3, I don't see it in CI4
         * $query_builder is mentioned but I don't see it in the CI4 code
         */


        $active_group = 'default';
        $query_builder = TRUE;

        // Config > app > juliet-ci-supplement > src > [parent]
        $f = __FILE__;
        $path = dirname(dirname(dirname(dirname($f))));
        if(!file_exists($path . '/private/config.php')){
            exit('file ' . $path . '/private/config.php is required and not present');
        }
        $MASTER_HOSTNAME = $MASTER_USERNAME = $MASTER_PASSWORD = $MASTER_DATABASE = '';
        require($path.'/private/config.php');

        $this->default['hostname'] = $MASTER_HOSTNAME;
        $this->default['username'] = $MASTER_USERNAME;
        $this->default['password'] = $MASTER_PASSWORD;
        $this->default['database'] = $MASTER_DATABASE;


		// Ensure that we always set the database group to 'tests' if
		// we are currently running an automated test suite, so that
		// we don't overwrite live data on accident.
		if (ENVIRONMENT === 'testing')
		{
			$this->defaultGroup = 'tests';
		}
	}

	//--------------------------------------------------------------------

}
