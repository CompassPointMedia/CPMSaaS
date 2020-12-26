<?php
namespace App\Controllers\Modules\SaaS;

use App\Controllers\Modules\SaaSController;
use App\Models\Auth;

class Data extends \App\Controllers\BaseController
{

    public $dbMaster;

    public $dbAccounts;

    public $subdomain;

    public $data;

    public $cvt;

    public function __construct(SaaSController $module = null) {

        // todo: this needs to be improved/split up/moved/organized
        helper('broadscope');

        // todo: improve/define the rules for injection between classes in the MRE system
        if ($module instanceof SaaSController) {
            $this->data = new \App\Models\Data($module->dbAccounts[$module->subdomain]);
            $this->dbMaster = $module->dbMaster;
            $this->dbAccounts = $module->dbAccounts;
            $this->subdomain = $module->subdomain;
            $this->cvt = new \App\Modules\InflectionPoint\Presentation\CVT( $this->dbAccounts[$this->subdomain] );
        } else {
            // Error T11

            // NOTE: letting this pass as this may be used for non-db and non-state methods
            // See CPMSAAS-8
        }

    }

    /**
     * @var array $dataObjects
     */
    protected $dataObjects = [];

    /**
     * This maps table_key to table_name
     * @var $dataObjectKeys
     */
    protected $dataObjectKeys = [];

    /**
     * @vetted
     *
     * @param string $object
     * @param string $layout - unused
     */
    public function view($object = '', $layout = '') {

        // Verify object is correct, table exists and user has access to table - see SaasController::validateSubdomain and ::validateUserAccessToSubdomain
        $table = $this->loadAccountTables(str_replace('-', '_', $object));

        $access = $this->validateUserAccessToTable($table['table_name']);

        if (empty($table) || !$access) {
            // Error T09
            throw new \App\Exceptions\GeneralFault(9);
        }

        //------------ CPMSAAS-7 -------------
        if (trim($table['js_config_main'])) {
            $javascript = trim($table['js_config_main']);
        } else {
            $javascript = $this->cvt->dataObjectJavascriptV2(
                $table['table_key'],
                $this->data->structure($this->actualTableName($table))
            );
        }

        $config = $this->cvt->dataObjectJavascriptConfigV1(
            $table['table_key']
        );

        echo view('pages/data_view', [
            'subdomain' => $this->subdomain,
            'table' => $table,
            'tables' => $this->dataObjects,
            'tableKeys' => $this->dataObjectKeys,
            'javascript' => $javascript,
            'config' => $config,
        ]);
    }

    public function manage($object = '', $config = '') {
        echo view('pages/data_manage', [
            'subdomain' => $this->subdomain,
            'tables' => $this->dataObjects,
            'tableKeys' => $this->dataObjectKeys,
        ]);
    }

    public function create($object = '', $config = '') {
        echo view('pages/data_create', [
            'subdomain' => $this->subdomain,
            'tables' => $this->dataObjects,
            'tableKeys' => $this->dataObjectKeys,
        ]);
    }

    public function help($object = '', $config = '') {
        echo view('pages/data_help', [
            'subdomain' => $this->subdomain,
            'tables' => $this->dataObjects,
            'tableKeys' => $this->dataObjectKeys,
        ]);
    }

    /**
     * Data Objects (tables) are stored in sys_table.  Both the table name and table key are unique.  The table_group can be any value, with the
     * default being `common`.
     */

    /**
     * @param null $tableOrKey
     * @param bool $override
     * @return mixed|null
     */
    public function loadAccountTables($tableOrKey = null, $override = false) {
        if (!$this->dataObjects || $override) {
            $this->dataObjectKeys = [];
            $this->dataObjects = [];
            $result = $this->dbAccounts[$this->subdomain]->query("SELECT * FROM sys_table");
            if ($a = $result->getResultArray()) {
                foreach ($a as $v) {
                    $this->dataObjects[strtolower($v['table_name'])] = $v;
                    $this->dataObjectKeys[strtolower($v['table_key'])] = $v['table_name'];
                }
            }
        }

        if ($tableOrKey) {
            $tableOrKey = strtolower($tableOrKey);
            return $this->dataObjects[$tableOrKey] ?? (!empty($this->dataObjectKeys[$tableOrKey]) ? $this->dataObjects[$this->dataObjectKeys[$tableOrKey]] : null);
        }
        return $this->dataObjects;
    }

    /**
     * See SaaSController::validateUserAccessToSubdomain - this is the same concept
     * @param $table
     * @return bool
     */
    public function validateUserAccessToTable($table) {
        // ------- redundant for here but we do it anyway -------
        if (empty($_SESSION['login'])) return false;

        $login = $_SESSION['login'];
        if (empty($login['active'])) return false;
        if (empty($login['accounts'][$this->subdomain])) return false;
        // ------------------------------------------------------

        if (!$table) {
            return false;
        }

        /* todo: we should also check the user role LIVE for each API action; for now just check user session role */
        $rank = max(array_keys($login['accounts'][$this->subdomain]['roles']));

        $record = $this->loadAccountTables(str_replace('-', '_', $table));

        if (! $record) {
            return false;
        }

        if ($record['table_access'] > $rank) {
            return false;
        }

        /* todo: note that System User and Admin would also have access; this shouldn't happen but it won't if we
         * don't put those perms in here, so document this in the Auth/Login class and make sure it's unit tested */
        if ($rank < Auth::MIN_SAAS_ACCESS_LEVEL) return false;

        return true;
    }

    /**
     * Convention for naming user tables
     *
     * @param $tableRow
     * @return string
     */
    public function actualTableName($tableRow) {
        if ($tableRow['literal'] == 1) {
            return $tableRow['table_name'];
        }
        return implode('_', [
            'pub', $tableRow['table_group'], $tableRow['table_name'], $tableRow['table_key']
        ]);
    }

    /**
     * @return string
     */
    public function generateTableKey() {
        $str = strtolower(chr( 64 + rand(1, 26)));
        $str .= strtolower(chr( 64 + rand(1, 26)));
        $str .= rand(1234, 9999);
        $str .= strtolower(chr( 64 + rand(1, 26)));
        $str .= strtolower(chr( 64 + rand(1, 26)));
        return $str;
    }


    public function export() {
        exit('data export');
    }
}