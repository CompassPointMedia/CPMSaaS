<?php
namespace App\Exceptions;

use Exception;

/**
 * General Fault Exception
 *
 * @author Samuel Fullman
 * @created 2020-12-23
 *
 */
class GeneralFault extends Exception
{

    /**
     * General Fault Errors developed sequentially while creating CompassPoint SAAS
     *
     * This simply passes the message on to the base Exception class; observed behavior appears to be:
     *
     *  1. logging will be done regardless including the stack trace
     *
     *  2. in production (.env file CI_ENVIRONMENT = production) you'll get the Whoops error. I really don't want that, I want something better eventually
     *
     *  3. otherwise you get a UI of the error and stack trace shows
     *
     * @var array
     */
    public $errors = [
        1   =>   'Unable to access ../private/config.php file',
        2   =>   'unable to locate subdomain $subdomain',
        3   =>   'Either not a valid subdomain, or user does not have valid access to the subdomain',
        4   =>   'I have not built out all my controllers or controller not recognized',
        5   =>   'System error, there\'s a role value in sys_role that\'s not in the AuthModel constants',
        6   =>   'Account configuration error, sys_table should at minimum be present and reachable in the account',
        7   =>   'Unknown or other account error',
        8   =>   'Attempt to access sys_* tables in account',
        9   =>   'User does not have access to table or table does not exist',
        10  =>   'Unrecognized table group or table request {dataGroupOrTable}',
        11  =>   'Improper interlock/asset sharing initialization between classes',
    ];

    public function __construct($code = 0, $message = '', Exception $previous = NULL) {

        if ($code === 0 && !$message) {
            $message = 'Unknown error';
        }

        if (empty($this->errors[$code])) {
            log_message('error', 'Hey buddy, watch it!  You passed me an error that I don\'t recognize!');
            $message = 'System error';
        }

        $message = 'Error T' . str_pad($code, 2, '0', STR_PAD_LEFT) . ': ' . ($message ?: $this->errors[$code]);

        parent::__construct($message, $code, $previous);
    }

}
