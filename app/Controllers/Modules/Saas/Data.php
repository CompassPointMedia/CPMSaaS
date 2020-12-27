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
     * @vetted
     *
     * @param string $object
     * @param string $layout - unused
     */
    public function view($object = '', $layout = '') {

        // Verify object is correct, table exists and user has access to table - see SaasController::validateSubdomain and ::validateUserAccessToSubdomain
        $table = $this->data->loadAccountTables(str_replace('-', '_', $object));

        $access = $this->data->validateUserAccessToTable($table['table_name'], $this->subdomain);

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
                $this->data->structure($this->data->actualTableName($table))
            );
        }

        $config = $this->cvt->dataObjectJavascriptConfigV1(
            $table['table_key']
        );

        echo view('pages/data_view', [
            'subdomain' => $this->subdomain,
            'table' => $table,
            'tables' => $this->data->dataObjects,
            'tableKeys' => $this->data->dataObjectKeys,
            'javascript' => $javascript,
            'config' => $config,
        ]);
    }

    public function manage($object = '', $config = '') {
        echo view('pages/data_manage', [
            'subdomain' => $this->subdomain,
            'tables' => $this->data->dataObjects,
            'tableKeys' => $this->data->dataObjectKeys,
        ]);
    }

    public function create($object = '', $config = '') {
        echo view('pages/data_create', [
            'subdomain' => $this->subdomain,
            'tables' => $this->data->dataObjects,
            'tableKeys' => $this->data->dataObjectKeys,
        ]);
    }

    public function help($object = '', $config = '') {
        echo view('pages/data_help', [
            'subdomain' => $this->subdomain,
            'tables' => $this->data->dataObjects,
            'tableKeys' => $this->data->dataObjectKeys,
        ]);
    }

    public function export() {
        exit('data export');
    }
}