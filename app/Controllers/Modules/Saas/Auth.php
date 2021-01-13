<?php

namespace App\Controllers\Modules\SaaS;

use \App\Controllers\BaseController;
use \App\Models\Auth as AuthModel;
use PHPUnit\Framework\Exception;

class Auth extends BaseController {

    public $subdomain = '';

    public $subdomainAccount = [];

    public $dbMaster;

    public $dbAccounts = [];

    /**
     * Auth constructor.  Negotiate assets with
     * @param $saasController
     */
    public function __construct($saasController = null) {

        if (!is_null($saasController)) {
            $this->subdomain = $saasController->subdomain;

            $this->dbMaster = $saasController->dbMaster;

            $this->dbAccounts = $saasController->dbAccounts;

        } else {
            $this->subdomain = determine_subdomain();

            $this->dbMaster = \Config\Database::connect('master');

        }
    }

    /**
     * @param array $data
     * @param array $config
     */
    public function renderAccountAccessLogin($data = [], $config = []) {
        /*
        --------------------------------------------------
        No judgement, they get this form:
        account: ___ <this subdomain, may be an account or not> ___ (filled in)
        your username: _______
        your password: _______
        success gets them to some main menu for members - this will be complex and eventually should involve bookmarking where they were prior
        failure gives them an "Unsuccessful" method with no more information
        I want emails to be unique values across all accounts
        Someone who has access in one account should be able to "try" to access any account.  We will eventually want to lead them all the way down the rabbit hole to this message: "If notanaccount is a valid SAAS account, an email will be sent to the administrator."  Tricked you, hackers..
        ---------------------------------------------------
        */
        $data['account'] = $data['account'] ?? $this->subdomain;

        echo view('pages/login_root', $data);
    }


    /**
     * password_version = 1 in db means that the password value is md5'd
     * password_version = 1 on request means the password value is md5'd, ie. it has been hashed before submission
     * Other numbers will mean additional hashing methods but md5 is good enough for now
     */
    public function processLogin() {

        // Begin login timer
        $start = microtime(true);

        // Process request variables
        foreach (['account', 'username', 'password', 'path', 'query', 'hash', 'password_version'] as $key) {
            $$key = $_REQUEST[$key] ?? '';
        }
        $requestAccount = $account;
        $requestPath = $path;
        $requestQuery = $query;
        $requestHash = $hash;

        if ($requestAccount !== $this->subdomain) {
            pre($requestAccount . ':' . $this->subdomain);
            // Cross account login attempt - we will support it but not yet
            // todo: log this
            $this->renderAccountAccessLogin(array_merge(
                $_REQUEST,
                ['error' => 'Cross account login is not supported at this time.  Make sure you account matches the subdomain in the URL.']
            ));
            return false;
        }


        // Set their basic user information in place
        // note joining with the log table might be slow eventually - Jira: archive the log table as that happened in Great Locations
        $sql = "SELECT u.*, MAX(l.create_time) AS last_login 
        FROM sys_user u LEFT JOIN sys_login l ON u.id = l.user_id 
        WHERE '$username' IN(email, username)
        GROUP BY u.id";
        $requestPassword = $password_version == 1 ? addslashes($password) : md5($password);
        $sqlPasswordSyntax = "IF(password_version = 1, password, MD5(password))";
        $sql .= " AND '$requestPassword' = $sqlPasswordSyntax";
        $query = $this->dbMaster->query($sql);
        $user = $query->getResultArray();
        if (empty($user[0])) {
            // Failed login - do nothing but do keep the same delay as success
            $elapsed = microtime(true) - $start;
            if ($elapsed < 2) {
                $sleep = 2000000 - $elapsed * 1000000;
                usleep($sleep);
            }

            //todo: pass an error - for right now I get a sequence of form resubmissions in the series, but hey even IBM w3id isn't smart enough to figure that out
            $this->renderAccountAccessLogin(array_merge(
                $_REQUEST,
                ['error' => 'Your login was not correct']
            ));
            return false;
        }
        $user = $user[0];
        $user_id = $user['id'];
        $user['create_time'] = strtotime($user['create_time']);
        $user['edit_time'] = strtotime($user['edit_time']);
        unset(
            $user['password'],
            $user['password_version']
        );
        if ($user['last_login']) {
            $user['last_login'] = strtotime($user['last_login']);
        }


        // Make sure they have account access
        $minSaasAccessLevel = AuthModel::MIN_SAAS_ACCESS_LEVEL;
        $sql = "SELECT 
        a.*, p.system_password, GROUP_CONCAT(aur.role_id) AS roles
        FROM sys_account_user_role aur 
        JOIN sys_account a ON aur.account_id = a.id
        JOIN sys_account_password p ON a.id = p.account_id
        WHERE aur.user_id = '$user_id'
        -- Security feature; make sure they have enough permissions to be in SAAS
        AND aur.role_id >= $minSaasAccessLevel
        GROUP BY a.id";
        $query = $this->dbMaster->query($sql);
        $accounts = $query->getResultArray();
        if (empty($accounts[0])) {
            // todo: they are a user, but they have no accounts.  This could happen, and it's cruel to say they're not logged in.  We should really tell them
            $elapsed = microtime(true) - $start;
            if ($elapsed < 2) {
                $sleep = 2000000 - $elapsed * 1000000;
                usleep($sleep);
            }
            // todo: pass an error - for right now I get a sequence of form resubmissions in the series, but hey even IBM w3id isn't smart enough to figure that out
            $this->renderAccountAccessLogin(array_merge(
                $_REQUEST,
                ['error' => 'Your login was not correct']
            ));
            return false;
        }


        // Get all lookup table roles
        $sql = "SELECT level, name FROM sys_role";
        $query = $this->dbMaster->query($sql);
        $result = $query->getResultArray();
        $roles = [];
        foreach ($result as $role) {
            $roles[$role['level']] = $role['name'];
        }


        // Put together all the account information
        $account_id = 'NULL';
        $accountSessionFields = [
            // Security: id is removed
            'name', 'identifier', 'unique_identifier', 'comments', 'create_time', 'edit_time', 'roles'
        ];
        foreach($accounts as $key => &$account) {
            if (is_string($key)) {
                continue;
            }
            $account['roles'] = explode(',', $account['roles']);
            $tmp = [];
            foreach($account['roles'] as $role) {
                if (empty($roles[$role])) {
                    // Error T05
                    throw new \App\Exceptions\GeneralFault(5);
                }
                $tmp[$role] = [
                    'value' => $role,
                    'name' => $roles[$role],
                    'description' => null,
                ];
            }
            $account['roles'] = $tmp;

            // Grab the account Db connection, and verify connection and that configuration looks good (will change over time)
            $template = config('Database')->master;
            $template['username'] = $account['system_username'];
            $template['password'] = $account['system_password'];
            $template['database'] = 'cpmsaas_' . $account['unique_identifier'];

            $cnx = $this->dbAccounts[$account['identifier']] = \Config\Database::connect($template);

            // todo: this try-catch is not shielding from the error I'm getting in CodeIgniter and of course that is something like:
            //      Table 'cpmsaas_T019D4965RS.sys_data_object' doesn't exist - which provides way too much information (but that's in dev)
            try {
                $query = $cnx->query('EXPLAIN sys_data_object');
                $results = $query->getResultArray();
                if (count($results) < 8) {
                    // Error T06
                    throw new \App\Exceptions\GeneralFault(6);
                }
            } catch (Exception $exception) {
                // Error T07
                throw new \App\Exceptions\GeneralFault(7);
            }

            // Unset unneeded fields
            if (strtolower($requestAccount) === $account['identifier']) {
                $account_id = $account['id'];
            }
            foreach ($account as $field => $value) {
                if (!in_array($field, $accountSessionFields)) {
                    unset($account[$field]);
                }
            }
            $account['create_time'] = strtotime($account['create_time']);
            $account['edit_time'] = strtotime($account['edit_time']);
            $accounts[$account['identifier']] = $account;

            unset($accounts[$key]);
        }

        // Does the user have root access?
        $minSystemAccessLevel = AuthModel::MIN_SYSTEM_ACCESS_LEVEL;
        $sql = "SELECT role_id FROM sys_account_user_role WHERE account_id IS NULL AND user_id = $user_id AND role_id >= $minSystemAccessLevel";
        $query = $this->dbMaster->query($sql);
        $result = $query->getResultArray();

        $root_access = [];
        if (! empty($result)) {
            foreach ($result as $permission) {
                $root_access[$permission['role_id']] = [
                    'value' => $permission['role_id'],
                    'name' => $roles[$permission['role_id']],
                    'description' => null,
                ];
            }
        }


        // Store this as a successful login
        $result = $this->dbMaster->query("INSERT INTO sys_login SET 
        user_id = $user_id,
        account_id = $account_id");
        $login_id = $result->connID->insert_id;


        // Set their session login!! Version 1.0
        // https://compasspointmedia.atlassian.net/wiki/spaces/CPMSAAS/pages/294918/Login+Structure
        $_SESSION['login'] = [
            'active' => true,       // I don't know why we'd need to set a user's session INACTIVE but might be less expensive than getting it again.
            'login_time' => $_SERVER['REQUEST_TIME'],
            'meta' => [
                'identity_level' => null, // 2020-11-29 this is whether or not they have done a "hard" login or a "soft" login through a link;
                // this may not be used but it is a concept I have used in the past and applies in cases where the user selects "re-
                // member me" when logging in, or comes in through a timed link with a hash of their password plus a salt.  In this case
                // they would need to "up their level" to do anything admin-related like change email or password.
                'login_ip' => $_SERVER['REMOTE_ADDR'],
                'login_id' => $login_id,
                'login_system' => 'unknown-TBD',
                'login_agent' => $_SERVER['HTTP_USER_AGENT'],                  // eg. browser
            ],
            'user' => $user,
            'accounts' => $accounts,
            'root_access' => $root_access,
        ];


        // Estimate this process to take 2 seconds
        // do really fancy analysis on login time and adjust so everyone gets the same.. but not now..
        $elapsed = microtime(true) - $start;
        if ($elapsed < 2) {
            $sleep = 2000000 - $elapsed * 1000000;
            usleep($sleep);
        }

        header('Location: /' . trim($requestPath, '/') . ($requestQuery ? '?' . ltrim($requestQuery, '?') : '') . ($requestHash ? '#' . ltrim($requestHash, '#') : ''));
        exit;
    }

    public function processLogout() {
        $_SESSION = [];
        header('Location: /');
        exit;
    }
}