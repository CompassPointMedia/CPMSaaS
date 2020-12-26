<?php

namespace App\Controllers\Modules;

use \App\Models\Auth as AuthModel;
use \App\Controllers\Modules\SaaS\Auth;
use \App\Libraries\ConnectionStore;

class SaaSController extends \App\Controllers\BaseController implements \CodeIgniter\Module\ModuleRoutingInterface
{

    /**
     * See other notes on these attributes
     */
    public $detectedLocale = null;

    public $controller = null;

    public $method = null;

    public $params = [];

    public $matchedRoute = null;

    public $matchedRouteOptions = null;

    public $controllerConstructor = null;

    public $dbMaster = null;

    public $dbAccounts = [];

    public $subdomain = '';

    private $subdomainAccount = null;

    /**
     * Whether the current call is an API call
     * @var null $api
     */
    public $api = null;

    /**
     * What SAAS Controller allows in the URL path.  Currently (12/15/2020) a single value but may allow for multiple, see parseUrl()
     * @var array $prefixes
     */
    public $prefixes = ['api'];

    public function __construct() {
        $this->dbMaster = \Config\Database::connect('master');
    }


    /**
     *
    @todo:
        search logs - have any of the passwords or usernames been logged?
        write a grep alerter if a user SQL username or password ever get in the session_tmp files
        eventually, have a encryption key so the database username and password in session can be reversibly encrypted texts and not the direct db username
        we use $_SESSION - consider using the session service

        allow compasspoint.compasspoint-saas.com or aec3d089f.compasspoint-saas.com
        so the results of determine_subdomain() will need to be translated..
     */


    /**
     * @param string $HTTPVerb
     * @param string $uri
     * @param array $module
     * @return bool
     */
    public function isMyUri(string $HTTPVerb, string $uri, array $module = []): bool
    {
        // 2020-11-28 as of right now, CompassPoint SAAS has no need for anything besides the main element of the subdomain
        // Although I don't like having to do it, users are stupid and share links, and so eventually we'll need to redirect
        // compasspoint.compasspoint-saas.com -> to -> a0a9adf70.compasspoint-saas.com
        // we'd need to do this with aliases for the tables as well
        $this->subdomain = determine_subdomain();

        
        if (!$this->subdomain) {
            // We'll let CI's routing table or Juliet handle this; initially just a contact us and sign-up form is needed
            // which we don't need to involve Module Routing for SaaS in..
            return false;
        }

        // Validate the subdomain - we do this regardless
        $this->subdomainAccount = $this->validateSubdomain($this->subdomain);

        if (! empty($_SESSION['login']['active'])) {

            // todo: if the subdomain is 'admin' then there's a higher level/standard of responsibility
            // There should be two levels of access, "God", and "angels", with those rights to be defined later
            // There needs to be an Account/Manage.php model which can handle reading, adding, updating and deleting accounts

            //Can someone be logged in as a compasspoint-saas staff and an account holder? Figure this out
            
            
            $hasAccess = $this->validateUserAccessToSubdomain($this->subdomain);

            if (!$this->subdomainAccount || !$hasAccess) {
                // The subdomain could be anything, but we want to log any attempts at non-existing domains for analytics and security
                // present the user with a "Your URL is incorrect. <Click to go home>".  If this current session shows any other valid subdomains
                // which they have accessed to <em>in this session</em>, give them links to go back, in fact to the last URL they were at
                // we have told the user nothing

                // Set the controller and route for this "404"ish page.

                // Error T03
                throw new \App\Exceptions\GeneralFault(3);
            }

            // FROM MASTER: get the admin rights of the user, status of the app, etc. - any pending updates, messages from the owner etc. etc.
            // todo: this is important, don't forget or the user has rights till they're logged out!


            // FOR ACCOUNT: set the default database connection
            $this->dbAccounts[$this->subdomain] = $this->setConnection($this->subdomainAccount);

            // Parse the URL
            $url = $this->parseUrl(null, ['prefixes' => $this->prefixes]);

            if (strtolower($url['controller']) === 'data') {
                if (strtolower($url['prefix']) === 'api') {
                    $this->controller = \App\Controllers\Modules\SaaS\DataApi::class;
                    $this->api = true;
                } else {
                    $this->controller = \App\Controllers\Modules\SaaS\Data::class;
                }
                $this->method     = $url['method'];
                $this->params     = $url['params'];

                $this->controllerConstructor = $this;       // in my view all of the controllers will take

            } else if (strtolower($url['controller']) === 'auth'){
                $this->controller = \App\Controllers\Modules\SaaS\Auth::class;
                $this->method     = $url['method'];
                $this->params     = $url['params'];

                $this->controllerConstructor = $this;       // in my view all of the controllers will take

            } else {
                // Error T04
                // todo: just build more, but yeah log this
                // todo: if it's the index page, that's going to need to ONLY be account login
                throw new \App\Exceptions\GeneralFault(4);
            }
        }
        else
        {
            // That means that either they're not logged in, or for some (future) (unspecified) reason Active = false;
            $this->controller = Auth::class;
            $this->controllerConstructor = $this;

            if (isset($_REQUEST['account']) && isset($_REQUEST['password'])) {
                $this->method = 'processLogin';
            } else {
                // Present login
                $this->method = 'renderAccountAccessLogin';
                // Note that we can't have keys on the params passed to the method, so we have to obey its rules
                // So it's best to pass array as parameters, though this may make the method very un-declarative
                $this->params = [
                    // data
                    [
                        'path' => $_SERVER['REDIRECT_URL'] ?? '',
                        'query' => $_SERVER['QUERY_STRING'] ?? '',
                        'hash' => $_SERVER['hash'] ?? '',
                    ],
                    // config - nothing for now
                    [

                    ]
                ];
            }

        }

        // Add our connections into a place available to the View class
        ConnectionStore::$dbMaster = $this->dbMaster;
        ConnectionStore::$dbAccounts = $this->dbAccounts;

        // We will handle this
        return true;
    }

    /**
     * Parse a given URL or by default the current request.
     * Allows for a single URL prefix like /en/Accounts/read or /api/data/request
     *
     * @param null $uri
     * @param array $config
     * @return mixed
     */
    public function parseUrl($uri = null, $config = [])
    {
        if (empty($config['prefixes'])) {
            $config['prefixes'] = [];
        }
        if (!$uri) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        $uri = explode('?', $uri);
        $urlString = trim($uri[0], '/');
        $parts = explode('/', $urlString);

        // Allow for a recognized prefix to be present
        $url['prefix'] = '';
        if (!empty($config['prefixes']) && in_array($parts[0], $config['prefixes'])) {
            $url['prefix'] = array_shift($parts);
        }

        $url['controller'] = array_shift($parts);
        $url['method'] = array_shift($parts);
        $url['params'] = $parts;
        $url['query_string'] = '';
        if (!empty($uri[1])) {
            parse_str($uri[1], $url['query_string']);
        }
        return $url;
    }

    /**
     * Validate that the subdomain exists.  In the future an account may be cancelled or deleted, and we'd want to say false
     * even if it's not there.
     *
     * @param string $subdomain
     * @return mixed
     */
    public function validateSubdomain(string $subdomain)
    {
        $query = $this->dbMaster->query("SELECT a.*, p.system_password FROM sys_account a JOIN sys_account_password p ON a.id = p.account_id WHERE a.identifier = '$subdomain'");
        $results = $query->getResult();

        if(empty($results[0])) {
            // we do not want to let the user know this domain doesn't exist, return false vs throwing an error
            log_message('error', 'The domain does not exist [' . $subdomain . ']');
            return false;
        }
        $subdomainAccount = $results[0];
        return $subdomainAccount;
    }

    /**
     * @param \stdClass $subdomainAccount
     * @return \CodeIgniter\Database\BaseConnection
     */
    public function setConnection(\stdClass $subdomainAccount)
    {
        $template = config('Database')->master;
        $template['username'] = $subdomainAccount->system_username;
        $template['password'] = $subdomainAccount->system_password;
        $template['database'] = 'cpmsaas_' . $subdomainAccount->unique_identifier;

        return \Config\Database::connect($template);
    }

    /* @todo these should probably be in the Auth class */
    /**
     * Validate that the user has access to the subdomain
     * As of right now we just return true if the user has SOME access, false if not or perhaps in the future revoked, or null if never-had
     * @param string $subdomain
     * @return mixed
     */
    public function validateUserAccessToSubdomain(string $subdomain) : bool
    {
        if (empty($_SESSION['login'])) return false;

        $login = $_SESSION['login'];
        if (empty($login['active'])) return false;
        if (empty($login['accounts'][$subdomain])) return false;

        /* @todo we should also check the user role LIVE for each API action; for now just check user session role */
        $rank = max(array_keys($login['accounts'][$subdomain]['roles']));

        /* @todo note that System User and Admin would also have access; this shouldn't happen but it won't if we
         * don't put those perms in here, so document this in the Auth/Login class and make sure it's unit tested */
        if ($rank < AuthModel::MIN_SAAS_ACCESS_LEVEL) return false;

        return true;
    }
}