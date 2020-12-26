<?php
namespace App\Libraries;

// Use another connection reference as desired.  This is just for the variable documentation anyway.
use CodeIgniter\Database\MySQLi\Connection;

class ConnectionStore
{
    /**
     * Store for master connection
     *
     * @var Connection $dbMaster
     */
    public static $dbMaster;

    /**
     * @var [Connection] $dbAccounts
     */
    public static $dbAccounts = [];
}

