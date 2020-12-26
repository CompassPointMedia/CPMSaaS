<?php namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Validation\ValidationInterface;

class Auth extends Model
{

    /**
     * Following roles found in table sys_role
     */
    CONST PERM_GOD = 65356;

    CONST PERM_SYSTEM_ADMIN = 4096;

    CONST PERM_SYSTEM_USER = 256;

    CONST PERM_SAAS_ADMIN = 32;

    CONST PERM_SAAS_USER = 16;

    CONST PERM_SAAS_GUEST = 4;

    CONST PERM_CPM_SAAS_ACCOLYTE = 2;

    /**
     * Minimum access levels.
     */
    CONST MIN_SAAS_ACCESS_LEVEL = 4;
    CONST MIN_SYSTEM_ACCESS_LEVEL = 256;


}