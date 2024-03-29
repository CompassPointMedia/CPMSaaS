<?php
namespace App\Models;

/**
 * Class Data
 * @author Samuel Fullman
 *
 * This was developed in CodeIgniter 3 and updated to work in CodeIgniter 4 December 2020
 *
 * Note that this model currently (12/15/2020) does NOT work with bulk updates. That process should be different and give meta stats like affected_rows.
 * Same goes for create or delete.
 */

use CodeIgniter\Model;
use App\Exceptions\GeneralFault;
use App\Libraries\IntKey;
use App\Libraries\StringKey;
use App\Libraries\SaasArray;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use PHPUnit\Framework\Exception;

class Data extends Model{

    public $cnx = null;

    public $limitStart = 0;

    public $limitRange = 100;

    public $dateTimeFormats = [
        'date' => ['/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', 'Y-m-d'],
        'time' => ['/^[0-9]{2}:[0-9]{2}([0-9]{2})*$/', 'H:i:s'],
        'datetime' => ['/^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}([0-9]{2})*)*$/', 'Y-m-d H:i:s'],
    ];

    public $defaultDateFormatHuman = 'F jS, Y g:iA';

    public $genericSqlNumberFields = 'int|integer|bigint|mediumint|smallint|tinyint|dec|decimal|float|double';

    public $pre_process_error = '';

    public $pre_process_comment = '';

    public $comparators = ['lt' => '<', 'le' => '<=', 'gt' => '>', 'ge' => '>=', 'ne' => '!='];

    public $dataGroup;

    public $dataGroups = [];

    public $processJoinedRootTableFirst = true;

    public $filePath = '';

    /**
     * @var $request SaasArray
     */
    public $request;

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
     * Maps id to lowercase of table name
     * @var array $dataObjectIds
     */
    protected $dataObjectIds = [];

    /**
     * Data constructor.
     * @param CodeIgniter\Database\MySQLi\Connection $cnx
     */
    public function __construct($cnx) {

        $this->cnx = $cnx;
        $this->filePath = APPPATH . '../writable/files/';
        /*

         todo: reinstate this - consider it part of this class
         //load models as necessary
         $this->load->model('Security_model');

         //because we often join one row to another table row for more information, and group by the unique key of the first row, I
         //see no reason not to easily pick non-grouped-by fields from the first row.
         //https://stackoverflow.com/questions/23921117/disable-only-full-group-by
         $this->cnx->query("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

         */

    }

    /**
     * Injects the information about the current dataGroup.
     * If you need to work with another dataGroup, instantiate another Data class

     * @param $dataGroupOrTable
     * @param array $meta
     * @return $this
     * @throws \App\Exceptions\GeneralFault
     */
    public function inject($dataGroupOrTable, $meta = []){

        $direct_access = $meta['direct_access'] ?? '';

        if ($direct_access === 'force' || ($direct_access === 'allow' && !isset($this->dataGroups[$dataGroupOrTable]))) {
            //build a pseudo dataGroup with no specifications besides db.table
            $this->dataGroup = [
                'name' => $dataGroupOrTable,
                'root_table' => $dataGroupOrTable,
                'direct_access' => true,
                'defaults' => [
                    'edit_time' => [
                        'create' => [
                            'value' => null
                        ],
                    ]
                ]
            ];

            // Approved list of injectors for a dataGroup which can be passed by meta
            $metaInjectors = ['relations'];
            foreach ($metaInjectors as $injector) {
                if (!isset($meta[$injector])) continue;
                $this->dataGroup[$injector] = $meta[$injector];
            }
            return $this;
        } else if (isset($this->dataGroups[$dataGroupOrTable])) {
            $this->dataGroup = $this->dataGroups[$dataGroupOrTable];
            //assign name
            $this->dataGroup['name'] = $dataGroupOrTable;
            if (empty($this->dataGroup['root_table'])) {
                exit('No root_table value for dataGroup `'. $dataGroupOrTable . '`');
            }
            return $this;
        }
        // Error T10
        throw new \App\Exceptions\GeneralFault(10);
    }

    /**
     * @vetted
     *
     * @param $request
     * @param array $meta
     * @return array
     *
     * meta:
     *      limitOverride: as implied, override any LIMIT clause (even if [limitStart and] limitRange are passed)
     */
    public function request($request, $meta = []){

        $this->request = new SaasArray($request);

        // streamline calls when we don't need items like $relations, $validation, $security etc.
        $minimal = !empty($meta['minimal']);

        $dataGroup = $this->dataGroup;
        $root_table = $dataGroup['root_table'];

        //control access
        if(empty($dataGroup['direct_access']) && isset($dataGroup['readable']) && $dataGroup['readable'] === false){
            return [
                'status_header' => 401,
                'error' => 'The data group `' . $dataGroup['name'] . '` is not readable based on this API call'
            ];
        }

        //pass security to FE for button configuration etc.
        if(!$minimal){
            $security = $this->security_access($dataGroup);
        }

        //parse limit
        if(isset($meta['limitOverride'])){
            $limit = '';
        }else{
            $foregoLimit = isset($dataGroup['forego_limit']) ? $dataGroup['forego_limit'] : false;

            $limitStart = (isset($request['limitStart']) ? $request['limitStart'] : '');
            $limitRange = (isset($request['limitRange']) ? $request['limitRange'] : '');

            //Javascript error protection
            $limitStart = str_replace('undefined', '', $limitStart);
            $limitRange = str_replace('undefined', '', $limitRange);

            if($foregoLimit && !strlen($limitStart) && !strlen($limitRange)){
                // forego limit clause
                $limit = '';
            }else{
                if(!strlen($limitStart)) $limitStart = (isset($dataGroup['limit_start']) ? $dataGroup['limit_start'] : $this->limitStart);
                if(!strlen($limitRange)) $limitRange = (isset($dataGroup['limit_range']) ? $dataGroup['limit_range'] : $this->limitRange);
                $limit = 'LIMIT ' . ($limitStart ? $limitStart . ', ' : '') . $limitRange;
            }
        }

        $start = microtime(true);

        //get structure
        $structure = $this->structure($root_table, $this->cnx->database);

        //markup structure with meta data
        foreach($structure as $n=> $v){
            $structure[$n]['table_alias'] = 'r';
            if(!empty($dataGroup['structure'][$n])) $structure[$n] = array_merge($structure[$n], $dataGroup['structure'][$n]);
        }


        /*
         * Handle mask requests
         *
         * This is fraught with danger because:
         * 1. I risk not having expected columns; fields may be needed for calculations
         * 2. Ironically I may risk security by giving a clue on what is NOT there
         *
         */
        $mask = $maskRequestValid = [];
        $maskRequest = empty($request['_mask']) ? [] :
            (is_array($request['_mask']) ? $request['_mask'] : explode(',', trim($request['_mask'])));


        foreach ($structure as $field => $null) {
            if (in_array($field, $maskRequest)) {
                $maskRequestValid[] = $field;
            }
        }

        //mask
        // if $maskRequestValid < $maskRequest - log a warning
        // if $maskRequest && !$maskRequestValid - throw an error
        if ($maskRequestValid) {
            $mask = $maskRequestValid;
        }

        // Bug S01 - if the user has control over the mask value, the user can request fields which are not intended
        // we almost need a library of expressions approved by an admin (e.g. concat(firstName, ' ', lastName) and not
        // concat(username, ':', password)
        foreach ($maskRequest as $additional) {
            // allow expressions
            if (!preg_match('/^[a-z0-9_]+$/i', $additional)) $mask[] = $additional;
        }

        //allow to hard-set field intent
        foreach(['datetime', 'date', 'time', 'timestamp'] as $key){
            foreach(!empty($dataGroup[$key]) ? $dataGroup[$key] : [] as $v){
                if(isset($structure[$v])){
                    $structure[$v]['intent'] = $key;
                }
            }
        }

        // Bug S02: this needs a significant amount of improvement; I don't believe it was ever used in Broadridge
        if(!empty($meta['field_list'])){
            $fieldList = $meta['field_list'];
        }else if(!empty($dataGroup['lookup_select'])){
            $fieldList = $dataGroup['lookup_select'];
        }else{
            $fieldList = !empty($dataGroup['field_list']) ? $dataGroup['field_list'] : 'r.*';
        }
        if ($mask) {
            $fieldList = implode(', ', $mask);
        }


        // Adds ability to pre_process a request (R) as well as is done with CUD
        if($this->pre_process('read', $request, [])){

            $where = $this->query_builder($request, $structure, $meta);
            $where = 'WHERE 1' . (!empty($dataGroup['base_where']) ? ' AND ' . $dataGroup['base_where'] : '') . ($where ? ' AND (' . $where  . ')' : '');



            // Bug S03: again, opening up a weakness
            $joins = $this->joinsToSQLString($this->joins);


            $orderBy = $this->order_by_builder(
                !empty($request['orderBy']) ? $request['orderBy'] : (!empty($dataGroup['base_order_by']) ? $dataGroup['base_order_by'] : ''),
                $structure
            );
            if($orderBy) $orderBy = 'ORDER BY '.$orderBy;

            $distinct = (!empty($dataGroup['distinct']) ? 'DISTINCT' : '');

            $sql = "SELECT SQL_CALC_FOUND_ROWS
                $distinct
                $fieldList
                
                FROM
                $root_table r
                $joins
                
                $where
        
                $orderBy
                
                $limit";
            $query = $this->cnx->query($sql);

            $stop = microtime(true);
            $query_took = round($stop - $start, 5);

            $dataset = $query->getResultArray();

            $query = $this->cnx->query('SELECT FOUND_ROWS() AS `Count`');
            $total_rows = $query->getRow()->Count;

            if (sizeof($dataset)){
                $page = (!empty($meta['limitOverride']) ? 0 : ($limitRange > 0 ? floor($limitStart / $limitRange) : 0)); // 0, 1, 2, 3, 4
            } else {
                $page = null;
            }

            //get validation rules
            if(!$minimal){
                $validation = $this->validate_rules($dataGroup);
            }

            //fulfill relationships if present
            if (!$minimal && (!empty($this->dataGroup['relations']))){
                $relations = [];

                foreach ($this->dataGroup['relations'] as $field => $relationSet){
                    if (empty($structure[$field])) {
                        // todo: log that a non-available relation was requested
                        continue;
                    }

                    if ($relation = $this->selectFieldRelation($relationSet)){
                        //instantiate a new Data model
                        //note that we may have or need more than one of them open for some type of cross-interaction
                        $_start = microtime(true);
                        $r[$field] = new self($this->cnx);

                        $relation_root_table = $this->actualTableName($this->fetchDataGroup($relation['identifier'])->rootDataObject);
                        $r[$field]->inject($relation_root_table, ['direct_access' => 'allow']);


                        //get the data with limited additional information





                        // ---------- todo: hack-alert: fix this -------------------
                        // ---------------------------------------------------------
                        $subRequest = [
                            '_mask' => [
                                'id',
                                $relation['label'] . ' AS _label',
                            ]
                        ];
                        $id = [];
                        if (empty($relation['filter'])) {
                            $relation['filter'] = 'needed';
                            foreach ($dataset as $data) {
                                $id[$data[$field]] = true;
                            }
                            $subRequest['id'] = '|IN|' . implode('|', array_keys($id));
                        }
                        $subMeta = ['minimal' => true, 'relation' => true];
                        // ---------------------------------------------------------
                        // ---------------------------------------------------------




                        
                        if ($result = $r[$field]->request($subRequest, $subMeta)){
                            $_stop = microtime(true);
                            $relations[$field] = [
                                'relation_lookup_took' => round($_stop - $_start, 5),
                                'query_took' => $result['query_took'],
                                'total_rows' => $result['total_rows'],
                                'structure' => $result['structure'],
                                'dataset' => $result['dataset'],
                                'query' => $result['query'],
                                'filter' => $relation['filter'],
                                'handle' => $relation['handle'] ?? 'default',
                            ];
                        }

                        //destroy the model
                        unset($r[$field]);
                    }
                }
            }

            return [
                'query_took' => $query_took,
                'total_rows' => $total_rows,
                'page' => $page,
                'structure' => $structure,
                'dataset' => $dataset,
                'validation' => empty($validation) ? [] : $validation,
                'relations' => empty($relations) ? [] : $relations,
                'security' => empty($security) ? [] : $security,
                'request' => $request,
                'query' => trim($sql),
            ];
        } else {
            return [
                'structure' => $structure,
                'dataset' => [],
                'request' => $request,
                'status_header' => 401,
                'error' => $this->pre_process_error,
            ];
        }
    }

    /**
     * @vetted
     *
     * @param array $request
     * @param array $meta
     * @return array
     */
    public function update($request, $meta = []){

        $dataGroup = $this->dataGroup;

        if(empty($dataGroup['direct_access']) && empty($dataGroup['updatable']) && empty($meta['force_update'])){
            if(empty($dataGroup['updatable'])){
                $changes = [];
                $changes['status_header'] = 401;
                $changes['error'] = 'This object does not allow for editing records';
                return $changes;
            }

            //Note! We have a bit of a quandary here because pre_process might also be an executing process, and do they have that privilege
            if(!empty($dataGroup['security']['access'])){
                $security = $this->security_access($dataGroup);
                if(isset($security['update']) && $security['update'] === false){
                    $changes = [];
                    $changes['status_header'] = 401;
                    $changes['error'] = (!empty($security['updateExceptionMessage']) ? $security['updateExceptionMessage'] : 'You do not have access to update this record');
                    return $changes;
                }
            }
        }

        //@todo, better to change the head request to UPDATE
        if(isset($request['update'])){
            $update = json_decode($request['update'], true);
        }else{
            $update = $request;
        }

        if(isset($request['_application'])){
            $update['_application'] = json_decode($request['_application'], true);
        }else if(!isset($update['_application'])){
            $update['_application'] = [];
        }

        //error checking here on the backend against permissions and allowable field values
        $check = 'OK';
        if(!$check){
            //inform the user

            exit;
        }

        $root_table = $dataGroup['root_table'];
        $structure = $this->structure($root_table);
        $primary = [];
        foreach($structure as $field => $v){
            if($v['primary_key']){
                if(empty($update[$field])) exit('No value passed for primary key field '.$field. ' in update variable');
                $primary[$field] = $update[$field];
            }
        }
        if(empty($primary)) exit('no primary key defined for '.$root_table);

        //pull the current record before update
        $original = $this->request($primary, ['minimal' => true]);
        $dataset = $original['dataset'][0]; // or error if not found by id

        //we do this because returned structure from request() has more analysis and information;
        //we just needed a basic structure to get the original.
        $structure = $original['structure'];

        //catalog changes
        $changes = [];
        $defaults = [];
        foreach($update as $n=>$v){
            //for now we do not allow updates to the primary key itself
            if(isset($primary[$n])) continue;

            if(!isset($structure[$n])) continue;

            if(!empty($dataGroup['specifically_limited_fields'])){
                if(!isset($dataGroup['specifically_limited_fields'][$n])) continue;
                if(!stristr($dataGroup['specifically_limited_fields'][$n], 'update')) continue;
            }

            if(!empty($dataGroup['restricted_fields'][$n]) && stristr($dataGroup['restricted_fields'][$n], 'update')) continue;


            //handle dataGroup defaults and overrides
            if(!empty($dataGroup['defaults'][$n])){
                foreach($dataGroup['defaults'][$n] as $modes => $default){

                    //indicate that we've dealt with this field default in the request
                    $defaults[$n] = true;

                    if(!stristr($modes, 'update')) continue;
                    //default method of handling defaults is to override what is passed
                    if(!isset($default['overrides'])) $default['overrides'] = true;
                    if($default['overrides']){
                        if(!empty($default['method'])){
                            $method = explode(':', $default['method']);
                            if(count($method) === 1){
                                $class = 'Data_model';
                                $method = $method[0];
                            }else{
                                $class = $method[0];
                                $method = $method[1];
                            }

                            //call the method
                            $this->load->model($class);
                            $action = null;
                            eval('$action = new \\' . $class . '();');
                            //2018-07-29 <sfullman@presidio.com> pass this field and overall request; allows for calculated values to be returned
                            $v = $update[$n] = $action->$method($n, $request);

                        }else if(!empty($default['value'])){
                            //this is a static value, or constant, or may have been set to some value like page_key by the constructor
                            $v = $update[$n] = $default['value'];
                        }else{
                            continue;
                        }
                    }else{
                        //go with the value passed in request
                    }
                }
            }
            if(isset($dataset[$n]) || is_null($dataset[$n])){
                // @todo improve comparison
                // 2018-08-21 <sfullman@presidio.com> wrapped passed value in prepare_correct_format so that 1/19/2019 (which is different than 2019-01-19) doesn't trigger a change
                if($this->prepare_correct_format($structure[$n], $update[$n]) != $dataset[$n]){
                    $changes[$n] = [
                        $dataset[$n],
                        $this->prepare_correct_format($structure[$n], $update[$n]),
                        'diff',             //type of action
                    ];
                }
            }else{
                //variable passed not in table - handle any security or change error
            }
        }

        //handle dataGroup defaults not specified above
        if(!empty($dataGroup['defaults'])){
            foreach($dataGroup['defaults'] as $field => $v){
                if(!$structure[$field]) continue;               //this column isn't present so why bother
                if(!empty($defaults[$field])) continue;         //already handled

                foreach($dataGroup['defaults'][$field] as $modes => $default){

                    # not really needed, nothing after this is looking
                    # $defaults[$field] = true;

                    if(!stristr($modes, 'update')) continue;

                    //default method of handling defaults is to override what is passed
                    if(!isset($default['overrides'])) $default['overrides'] = true;
                    if($default['overrides']){
                        if(!empty($default['method'])){
                            $method = explode(':', $default['method']);
                            if(count($method) === 1){
                                $class = 'Data_model';
                                $method = $method[0];
                            }else{
                                $class = $method[0];
                                $method = $method[1];
                            }

                            //call the method
                            $this->load->model($class);
                            $action = null;
                            eval('$action = new \\' . $class . '();');
                            //2018-07-29 <sfullman@presidio.com> pass this field and overall request; allows for calculated values to be returned
                            $update[$field] = $action->$method($field, $request);

                        }else if(!empty($default['value'])){
                            //this is a static value, or constant, or may have been set to some value like page_key by the constructor
                            $update[$field] = $default['value'];
                        }else{
                            continue;
                        }

                        $changes[$field] = [
                            null,
                            $this->prepare_correct_format($structure[$field], $update[$field]),
                            'diff',             //type of action
                        ];
                    }else{
                        //this is post-request; no value requested, go with db table default if present
                    }
                }
            }
        }

        if($changes){
            // pre_process returns true if there is no preprocessing required
            if($this->pre_process('update', $update, $changes)){
                // Yay, let's update our records also
                $changelog_comment = !empty($this->pre_process_comment) ? $this->pre_process_comment : (!empty($meta['changelog_comment']) ? $meta['changelog_comment'] : '');

                $paramStr = '';
                $changelog = (!isset($meta['changelog']) || $meta['changelog'] === true);
                $aggregate_changelog = !empty($dataGroup['aggregate_changelog']) || !empty($meta['aggregate_changelog']);

                //log changes as diffs in changelog record
                foreach($changes as $field => $change){
                    $paramStr .= ($paramStr ? ','."\n" : '') . $field . ' = ' .
                        (is_null($change[1]) ? '' : '\'') .
                        (is_null($change[1]) ? 'NULL' : str_replace("'", "\\'", $change[1])) .
                        (is_null($change[1]) ? '' : '\'');

                    if($changelog && !$aggregate_changelog){
                        $sql = "INSERT INTO sys_changelog SET 
                        object_name = '$root_table',
                        object_key = '" . str_replace("'", "\\'", implode('-', $primary)) . "',
                        data_source = 'user',
                        type = 'value change',
                        comment = " . ($changelog_comment ? "'" . str_replace("'", "\\'", $changelog_comment) . "'" : 'NULL') . ",
                        creator = '" . (!empty($_SESSION['UserName']) ? $_SESSION['UserName'] : 'unknown-user') . "',
                        affected_element = '$field',
                        change_from = " .
                            (is_null($change[0]) ? '' : "'") .
                            (is_null($change[0]) ? 'NULL' : str_replace("'", "\\'", $change[0])) .
                            (is_null($change[0]) ? '' : "'") . ",
                            change_to = " .
                            (is_null($change[1]) ? '' : "'") .
                            (is_null($change[1]) ? 'NULL' : str_replace("'", "\\'", $change[1])) .
                            (is_null($change[1]) ? '' : "'");
                        $this->cnx->query($sql);
                    }
                }

                if($changelog && $aggregate_changelog){
                    // make a single entry
                    $sql = "INSERT INTO sys_changelog SET 
                    object_name = '$root_table',
                    object_key = '" . str_replace("'", "\\'", implode('-', $primary)) . "',
                    data_source = 'user',
                    type = 'value change',
                    comment = " . ($changelog_comment ? "'" . str_replace("'", "\\'", $changelog_comment) . "'" : 'NULL') . ",
                    creator = '" . (!empty($_SESSION['UserName']) ? $_SESSION['UserName'] : 'unknown-user') . "',
                    affected_element = '(aggregate)',
                    change_from = NULL,
                    change_to = '".$this->array_to_json_in_field($changes)."'";
                    $this->cnx->query($sql);
                }

                $where = '';
                foreach($primary as $field => $v){
                    $where .= ($where ? ' AND ' : '') . $field . '=\'' . str_replace("'", "\\'", $v) . '\'';
                }

                $sql = "UPDATE $root_table SET 
                    $paramStr
                    WHERE $where";
                $this->cnx->query($sql);
            }else{
                //error from REMEDY; we probably shouldn't update our db information unless we come up with some kind of status = queueing|waiting
                //for now, we do this
                $changes['status_header'] = 401;
                $changes['error'] = $this->pre_process_error;
            }
        }else{
            //well, no changes requested.. maybe we should tell them.  But it probably should not have happened..
        }
        $changes['request'] = $request;
        return $changes;
    }

    /**
     * @vetted
     *
     * @param array $request
     * @param array $meta
     * @return array
     */
    public function insert($request, $meta = []){

        //get structure for primary key
        $dataGroup = $this->dataGroup;

        if(empty($dataGroup['direct_access']) && empty($dataGroup['insertable']) && empty($meta['force_insert'])){
            $changes = [];
            $changes['status_header'] = 401;
            $changes['error'] = 'This object does not allow for adding new records';
            return $changes;
        }

        //@todo, better to change the head request to INSERT
        if(isset($request['insert'])){
            $insert = json_decode($request['insert'], true);
        }else{
            $insert = $request;
        }

        if(isset($request['_application'])){
            $insert['_application'] = json_decode($request['_application'], true);
        }else if(!isset($insert['_application'])){
            $insert['_application'] = [];
        }

        //error checking here on the backend against permissions and allowable field values
        $check = 'OK';
        if(!$check){
            //inform the user

            exit;
        }

        $root_table = $dataGroup['root_table'];
        $structure = $this->structure($root_table);
        $primary = [];
        foreach($structure as $field => $v){
            if($v['primary_key']){
                $primary[$field] = (!empty($insert[$field]) ? $insert[$field] : '');
            }
        }
        if(count($primary) > 1){
            foreach($primary as $field => $value){
                if(!strlen($value)){
                    exit('Compound primary keys must have both values declared for an insert; no value found for '.$field);
                }
            }
        }

        //catalog changes
        $changes = [];
        $defaults = [];
        foreach($insert as $n=>$v){
            if(empty($structure[$n])) continue;     //not a field

            //handle changes which should not be made
            if(!strlen($v)){
                if( !empty($dataGroup['omitBlankInserts']) && in_array($n, $dataGroup['omitBlankInserts'])){
                    continue;
                }else if($structure[$n]['default'] === 'CURRENT_TIMESTAMP'){
                    continue;
                }else if($structure[$n]['default'] === null) {
                    //MySQL EXPLAIN returns a literal null value when the default is null
                    continue;
                }
            }
            if(!empty($dataGroup['specifically_limited_fields'])){
                if(!isset($dataGroup['specifically_limited_fields'][$n])) continue;
                if(!stristr($dataGroup['specifically_limited_fields'][$n], 'insert')) continue;
            }

            //handle dataGroup defaults and overrides
            if(!empty($dataGroup['defaults'][$n])){
                foreach($dataGroup['defaults'][$n] as $modes => $default){

                    //indicate that we've dealt with this field default in the request
                    $defaults[$n] = true;

                    if(!stristr($modes, 'create')) continue;
                    //default method of handling defaults is to override what is passed
                    if(!isset($default['overrides'])) $default['overrides'] = true;
                    if($default['overrides']){
                        if(!empty($default['method'])){
                            $method = explode(':', $default['method']);
                            if(count($method) === 1){
                                $class = 'Data_model';
                                $method = $method[0];
                            }else{
                                $class = $method[0];
                                $method = $method[1];
                            }

                            //call the method
                            $this->load->model($class);
                            $action = null;
                            eval('$action = new \\' . $class . '();');
                            //2018-07-29 <sfullman@presidio.com> pass this field and overall request; allows for calculated values to be returned
                            $insert[$n] = $action->$method($n, $request);

                        }else if(array_key_exists('value', $default)){
                            //this is a static value, or constant, or may have been set to some value like page_key by the constructor
                            $insert[$n] = is_null($default['value']) ? null : $default['value'];
                        }else{
                            continue;
                        }
                    }else{
                        //go with the value passed in request
                    }
                }
            }

            $changes[$n] = [
                null,
                $this->prepare_correct_format($structure[$n], $insert[$n]),
                'diff',             //type of action
            ];
        }

        //handle dataGroup defaults not specified above
        if(!empty($dataGroup['defaults'])){
            foreach($dataGroup['defaults'] as $field => $v){
                if(empty($structure[$field])) continue;         //this column isn't present so why bother
                if(!empty($defaults[$field])) continue;         //already handled

                foreach($dataGroup['defaults'][$field] as $modes => $default){

                    # not really needed, nothing after this is looking
                    # $defaults[$field] = true;

                    if(!stristr($modes, 'create')) continue;

                    //default method of handling defaults is to override what is passed
                    if(!isset($default['overrides'])) $default['overrides'] = true;
                    if($default['overrides']){
                        if(!empty($default['method'])){
                            $method = explode(':', $default['method']);
                            if(count($method) === 1){
                                $class = 'Data_model';
                                $method = $method[0];
                            }else{
                                $class = $method[0];
                                $method = $method[1];
                            }

                            //call the method
                            $this->load->model($class);
                            $action = null;
                            eval('$action = new \\' . $class . '();');
                            //2018-07-29 <sfullman@presidio.com> pass this field and overall request; allows for calculated values to be returned
                            $insert[$field] = $action->$method($field, $request);

                        }else if(!empty($default['value'])){
                            //this is a static value, or constant, or may have been set to some value like page_key by the constructor
                            $insert[$field] = $default['value'];
                        }else{
                            continue;
                        }

                        $changes[$field] = [
                            null,
                            $this->prepare_correct_format($structure[$field], $insert[$field]),
                            'diff',             //type of action
                        ];
                    }else{
                        //this is post-request; no value requested, go with db table default if present
                    }
                }
            }
        }

        if($changes){
            // pre_process returns true if there is no preprocessing required
            if($this->pre_process('insert', $insert, $changes)) {
                $paramStr = '';
                foreach($changes as $field => $change){
                    if(isset($primary[$field]) && !strlen($change[1])) continue;
                    $paramStr .= ($paramStr ? ','."\n" : '') . $field . ' = ' .
                        (is_null($change[1]) ? '' : '\'') .
                        (is_null($change[1]) ? 'NULL' : str_replace("'", "\\'", $change[1])) .
                        (is_null($change[1]) ? '' : '\'');
                }

                $sql = "INSERT INTO $root_table SET $paramStr";
                $this->cnx->query($sql);

                if(count($primary) > 1){
                    $query = $this->cnx->table($root_table)->getWhere($primary);
                    $primaryKeyString = implode('-', $primary);
                }else{
                    $insert_id = $this->cnx->connID->insert_id;
                    $key = implode('', array_keys($primary));
                    $query = $this->cnx->table($root_table)->getWhere([$key => $insert_id]);
                    $primaryKeyString = $insert_id;
                }

                //insert the record, but no changes in the field values
                $changelog = (!isset($meta['changelog']) || $meta['changelog'] === true);
                if($changelog){
                    $changelog_comment = !empty($this->pre_process_comment) ? $this->pre_process_comment : (!empty($meta['changelog_comment']) ? $meta['changelog_comment'] : '');
                    $sql = "INSERT INTO sys_changelog SET 
                    object_name = '$root_table',
                    object_key = '$primaryKeyString',
                    data_source = 'user',
                    type = 'insert record',
                    comment = " . ($changelog_comment ? "'" . str_replace("'", "\\'", $changelog_comment) . "'" : 'NULL') . ",
                    creator = '" . (!empty($_SESSION['UserName']) ? $_SESSION['UserName'] : 'unknown-user') . "',
                    affected_element = '(aggregate)',
                    change_from = NULL,
                    change_to = '" . $this->array_to_json_in_field($changes) . "'";
                    $this->cnx->query($sql);
                }

                $dataset = [];
                foreach($query->getResultArray() as $dataset){
                    break;
                }
                $changes['request'] = $request;
                $changes['dataset'] = $dataset;
            }else{
                //error from REMEDY; we probably shouldn't update our db information unless we come up with some kind of status = queueing|waiting
                //for now, we do this
                $changes['status_header'] = 401;
                $changes['error'] = $this->pre_process_error;
            }
        }else{
            //well, no changes requested.. maybe we should tell them.  But it probably should not have happened..
        }
        $changes['request'] = $request;
        return $changes;
    }

    /**
     * @vetted
     *
     * @param mixed|string $request
     * @param array $meta
     * @return array
     */
    public function delete($request, $meta = []){

        //@todo, better to change the head request to DELETE
        if(isset($request['delete'])){
            $delete = json_decode($request['delete'], true);
        }else{
            $delete = $request;
        }

        //get structure for primary key
        $dataGroup = $this->dataGroup;

        if(empty($dataGroup['direct_access']) && empty($dataGroup['deletable']) && empty($meta['force_delete'])){
            $changes = [];
            $changes['status_header'] = 401;
            $changes['error'] = 'This object does not allow for deletion of records';
            return $changes;
        }

        $root_table = $dataGroup['root_table'];
        $structure = $this->structure($root_table);
        $primary = [];
        foreach($structure as $field => $v){
            if($v['primary_key']){
                if(empty($delete[$field])) exit('No value passed for primary key field '.$field. ' in delete variable');
                $primary[$field] = $delete[$field];
            }
        }
        if(empty($primary)) exit('no primary key defined for '.$root_table);

        // pre_process returns true if there is no preprocessing required
        if($this->pre_process('delete', $delete, [])){
            // Let's delete the record

            $where = '';
            foreach($primary as $field => $v){
                $where .= ($where ? ' AND ' : '') . $field . '=\'' . str_replace("'", "\\'", $v) . '\'';
            }

            $sql = "DELETE FROM $root_table 
                    WHERE $where";
            $this->cnx->query($sql);

            $changelog = (!isset($meta['changelog']) || $meta['changelog'] === false);
            if($changelog){
                $changelog_comment = !empty($this->pre_process_comment) ? $this->pre_process_comment : (!empty($meta['changelog_comment']) ? $meta['changelog_comment'] : '');
                $sql = "INSERT INTO sys_changelog SET
                    object_name = '$root_table',
                    object_key = '" . str_replace("'", "\\'", implode('-', $primary)) . "',
                    data_source = 'user',
                    type = 'delete record',
                    comment = " . ($changelog_comment ? "'" . str_replace("'", "\\'", $changelog_comment) . "'" : 'NULL') . ",
                    creator = '" . (!empty($_SESSION['UserName']) ? $_SESSION['UserName'] : 'unknown-user') . "',
                    affected_element = '(aggregate)',
                    change_from = NULL,
                    change_to = NULL";
                $this->cnx->query($sql);
            }
        }else{
            //error from REMEDY; we probably shouldn't update our db information unless we come up with some kind of status = queueing|waiting
            //for now, we do this
            $changes['status_header'] = 401;
            $changes['error'] = $this->pre_process_error;
        }

        $changes['request'] = $request;
        return $changes;
    }

    /**
     * @new
     *
     * @param $request
     * @param array $meta
     * @return array
     */
    public function create($request, $meta = []){
        /**
         * OK in creation alone there are a gazillion things to do
         * are we creating a table, or also importing data
         * we need to be thinking in terms of context
         * todo: should we keep all CompasssPoint SAAS stuff out of this and have the SAAS Model depend on the data model
         * todo: table max length - make sure not to exceed
         */

        // validate the request
            // is the group/table already in existence
            // is the table name legal
            // we can't have indexes on text fields without an index length; see indexCreate todo

        $trial = !empty($request['trial']);
        $title = $request['title'] ?? '';
        $description = $request['description'] ?? '';


        $tableName = $request['tableName'];
        $tableComment = $request['tableComment'] ?? '';
        $tableGroup = $request['tableGroup'] ?? 'common';
        $db = $request['db'];
        $tableEngine = $request['tableEngine'] ?? 'InnoDB';
        $tableCharset = $request['tableCharset'] ?? 'latin1';

        // Basic error checking
        $error = [];
        if (!trim($tableName)) {
            $error = [
                'status_header' => '480',
                'status_message' => 'Please enter a valid table name',
            ];
        } else {
            $sql = "SELECT * FROM sys_data_object WHERE table_name = '". addslashes($tableName) . "'";
            $query = $this->cnx->query($sql);
            $result = $query->getResultArray();
            if (!empty($result[0])) {
                $error = [
                    'status_header' => '481',
                    'status_message' => 'Access denied based on this table name',
                ];
            }
        }
        if (!empty($error)) {
            return $error;
        }

        // Create indexes string; check first if a primary is created by user
        // todo: there should be some warning or error if the user tries this and it's not the field `id` as we kind of want to use that in CVT etc.
        $config = [
            'return_array' => true,
        ];
        $indexCreate = $this->indexCreate($request['fields'], $db, $config);
        $hasPrimary = isset($indexCreate['primary']['primary']);
        $indexCreateString = $this->indexCreate($request['fields'], $db, []);


        // Create fields string
        $config = [
            'add_id_column' => !$hasPrimary,
        ];
        $fieldCreateString = $this->fieldCreateString($request['fields'], $db, $config);
        if ($indexCreateString) {
            $fieldCreateString .= ',' . "\n";
        }

        // Create meta/table information string
        // Safe to say that MYSQL has a 60 character liimit on table comments
        $generated = date('F jS, Y \a\t g:i:sA');
        $tableMetaString = "Engine=$tableEngine DEFAULT CHARSET=$tableCharset";
        if (empty($tableComment)) {
            // Create system comment
            $tableComment = 'Created by Data::create() ' . $generated;
        } else {
            $tableComment = substr($tableComment, 0, 60);
        }
        $tableMetaString .= ' COMMENT=\'' . str_replace('\'', '\'\'', $tableComment) . '\'';

        $tableKey = $this->generateTableKey();
        $actualTableName = $this->actualTableName([
            'table_name' => $tableName,
            'table_key' => $tableKey,
        ]);
        $tableNameString = $trial ? $tableName : $actualTableName;


        // Write the create table syntax
        $sql = "CREATE TABLE `$tableNameString`(
-- CREATE TABLE SQL generated by Data::create() $generated 
$fieldCreateString
$indexCreateString
) $tableMetaString";

        if (!$trial) {
            // actually create the table
            $this->cnx->query($sql);

            $initialConfig = json_encode($request['fields'], true);
            $initialConfig = str_replace(':"false"', ':false', $initialConfig);
            $initialConfig = str_replace(':"true"', ':true', $initialConfig);

            $group_id = $this->fetchOrCreateTableGroup($tableGroup)['id'];

            // enter the table in sys_data_object
            $sys_data_object_insert = "INSERT INTO sys_data_object SET 
            group_id = '$group_id', 
            title = '" . addslashes($title) . "',
            description = '" . addslashes($description) . "',
            table_name = '" . addslashes($tableName) . "',
            table_key = '" . addslashes($tableKey) . "',
            initial_config = '" . addslashes($initialConfig) . "'";
            $this->cnx->query($sys_data_object_insert);

            $status_message = "CREATE TABLE executed successfully: $tableName";
            $message = []; // todo: develop this

        } else {
            $status_message = "CREATE TABLE SQL successfully generated";
            $message = [
                'sql' => $sql
            ];
        }
        return [
            'status_header' => 299,
            'status_message' => $status_message,
            'message' => $message,
        ];

        /**
         * 2020-12-24 todo:
         *      1. handle return to the controller - could be a result, but should always include the creation string with a str_replace to replace the
         *      2. creation string should also include a simple INSERT statement with reasonable data
         *      3. parse out the return string in SQL highlighting/syntax
         *      4. "Export SAAS table definition" - from existing table
         *      5. do the popoup for now like I planned
         *      6. show any existing table in edit mode in this interface
         *      7. handle MYSQL table length limits
         */
    }

    /**
     * This is a very hackable and temporary method
     * @new
     * @created 2021-01-09
     *
     * @param $nameOrId
     * @return mixed
     */
    public function fetchOrCreateTableGroup($nameOrId) {
        $query = $this->cnx->query("SELECT * FROM sys_data_object_group WHERE '" . addslashes($nameOrId) . "' IN(id, name)");
        if (($result = $query->getResultArray()) && !empty($result[0])) {
            return $result[0];
        } else {
            if (!preg_match('/^[0-9]+$/', $nameOrId)) exit('Group name must not be a number');
            $sql = "INSERT INTO sys_data_object_group SET name = '" . addslashes($nameOrId) . "'";
            $result = $this->cnx->query($sql);
            if ($result) {
                return $this->cnx->connID->insert_id;
            }
            //todo: log error
            return null;
        }
    }

    /**
     * @new
     * @created 2020-12-23
     *
     * @param $fields
     * @param $db
     * @param $config array
     * @return string
     */
    public function fieldCreateString($fields, $db, $config = []) {
        if ($db !== 'mysql') exit('only MYSQL database tables supported');

        $addIdColumn = !empty($config['add_id_column']);
        $idLength = $config['id_length'] ?? 14;

        /**
         * This is intended to take the same array field names that MySQL provides when using EXPLAIN
         * TYPE is going to be something like
         * do we restrict or have rules on the name
         */
        $string = '';

        // todo finish this list authoritatively and include the MYSQL documentation link(s)
        $noSignedAttribute = ['TEXT', 'DATE', 'TIME', 'DATETIME', 'CHAR', ];
        $noLengthAttribute = ['TEXT', 'DATE', 'TIME', 'DATETIME'];

        /**
         * As I deal with all kinds of different rules, this seems the best way for now to manage them
         * As of 12/23/2020 these are "recoverable" mistakes, things we just ignore.
         */
        $rules = [
            'kindsThatCannotBeUnsigned' => ['TIMESTAMP'],
        ];

        $id = false;
        foreach ($fields as $field) {
            if ($string) $string .= ",\n";

            if (strtolower($field['Field']) === 'id') {
                $id = $field['Field'];
            }
            $string .= '   `' . $field['Field'] . '`';

            if (isset($field['Type'])) {
                // If "Type" is present then we concat it literally; this would come from a MYSQL `EXPLAIN` command
                $string .= ' ' . $field['Type'];
            } else {
                // If "Kind" is present we presume the Sequel Pro-style fields eg: Type | Length | Unsigned | Zerofill | Binary
                $string .= ' ' . $field['Kind'];
                if (!in_array($field['Kind'], $noLengthAttribute) && trim($field['Length'])) {
                    $string .= ' (' . $field['Length'] . ')';
                }
                if (!in_array($field['Kind'], $noSignedAttribute) && $field['Unsigned']) {
                    if (!in_array($field['Kind'], $rules['kindsThatCannotBeUnsigned'])) {
                        $string .= ' UNSIGNED';
                    }
                }
                if (!empty($field['Zerofill'])) {
                    $string .= ' ZEROFILL';
                }
                if (!empty($field['Binary'])) {
                    $string .= ' BINARY';
                }
            }

            $string .= (!empty($field['Null']) ? '' : ' NOT') . ' NULL';

            if (!empty($field['Default']) && strlen($field['Default'])) {
                // todo: there are other values besides CURRENT_TIMESTAMP to exclude from quotes
                $quote =
                    preg_match('/^[-.0-9]+$/', $field['Default']) ||
                    in_array(strtoupper($field['Default']), ['NULL', 'CURRENT_TIMESTAMP']) ? '' : '\'';
                $string .= ' DEFAULT ' . $quote . ($quote ? str_replace('\'', '\'\'', $field['Default']) : $field['Default']) . $quote;
            }
            if (!empty($field['Extra']) && strlen($field['Extra'])) {
                $string .= ' ' . $field['Extra'];
            }
            if (!empty($field['Comment']) && strlen($field['Comment'])) {
                $string .= ' COMMENT \'' . str_replace('\'', '\'\'', $field['Comment']) . '\'';
            }

        }
        if($addIdColumn && !$id) {
            // Add id as first field
            $string = '   `id` INT(' . $idLength . ') UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,' . "\n" . $string;
        }
        return $string;
    }

    /**
     * @new
     * @created 2020-12-23
     *
     * @param $fields
     * @param $db
     * @param array $config
     * @return string
     *
     * @todo    handle fulltext indexing
     * @todo    handle index *lengths* - pretty sure this can be done with this syntax
     * @todo    allow the user to keep their case for indexes
     */
    public function indexCreate($fields, $db, $config = []) {
        if ($db !== 'mysql') exit('only MYSQL database indexes supported');

        $return_array = $config['return_array'] ?? false;

        // Example of how indexes will look
        /*
        $primary = [
            'primary' => [ // this extra key to conform to other array values
                'student_id', 'class_id',
            ]
        ];
        $unique = [
            'uuid' => [
                'uuid',
            ],
            'birthdate-ssn' => [
                'birthdate', 'ssn'
            ]
        ];
        $indexes = [
            'field1-field2' => [ //user selected name
                'field1', 'field2',
            ],
            'field3' => [
                'field3',
            ]
        ];
        */

        $primary = $unique = $indexes = $mul = [];
        foreach ($fields as $field) {
            if (empty($field['Key'])) continue;
            $requests = preg_split('/[, ]+/', strtolower($field['Key']));
            foreach ($requests as $index) {
                // todo: allow the user to keep their case for indexes
                $index = strtolower($index);

                // NOTE: MYSQL EXPLAIN has values of PRI, UNI, and MUL in the Key field.
                // We can interpret PRI and UNI here, but MUL can't be parsed
                // To do that you'd need to use the `SHOW INDEXES FROM table_name` command, which is not addressed here, but the below arrays are similar
                if ($index === 'mul') {
                    $mul[] = $field['Field'];
                    continue;
                }

                switch (true) {
                    case $index === 'p':
                    case $index === 'pri':
                    case $index === 'primary':
                        // Primary key request
                        if (isset($primary['primary'])) {
                            $primary['primary'][] = $field['Field'];
                        } else {
                            $primary['primary'] = [$field['Field']];
                        }
                        break;
                    case $index === 'y':
                        // Single-field key request
                        $indexes[$field['Field']] = [$field['Field']];
                        break;
                    case substr($index, 0, 2) === 'u-' && strlen($index) > 2:
                        // Unique key possible-multiple request
                        // If the user has the string after u- be a field name, there could be a problem
                        $index = substr($index, 2 - strlen($index));

                        if (isset($unique[$index])) {
                            $unique[$index][] = $field['Field'];
                        } else {
                            $unique[$index] = [$field['Field']];
                        }
                        break;
                    case $index === 'u':
                    case $index === 'uni':
                    case $index === 'unique':
                        // Single-field unique key request
                        $unique[$field['Field']] = [$field['Field']];
                        break;

                    default:
                        // Non-unique key possible-multiple request
                        // If the user has the string be a field name, there could be a problem
                        if (isset($indexes[$index])) {
                            $indexes[$index][] = $field['Field'];
                        } else {
                            $indexes[$index] = [$field['Field']];
                        }
                }
            }

        }

        // Return array
        if ($return_array) {
            return [
                'primary' => $primary,
                'unique' => $unique,
                'indexes' => $indexes,
                'undefined-multiple' => $mul,
                'documentation' => 'This is indexCreate parsing of CompassPoint SAAS enhanced Key field definition.  Note the `undefined-multiple` node indicates any keys coming directly from a MYSQL `EXPLAIN my_table` command, and you would need to execute `SHOW INDEXES FROM my_table` for further information',
            ];
        }

        // Return string
        $string = '';
        if (!empty($primary)) {
            if (!empty($string)) $string .= ',' . "\n";
            $string .= '   PRIMARY KEY (`' . implode('`, `', $primary['primary']) . '`)';
        }
        if (!empty($unique)) {
            foreach ($unique as $name => $u) {
                if (!empty($string)) $string .= ',' . "\n";
                $string .= '   UNIQUE KEY `' . $name . '` (`' . implode('`, `', $u) . '`)';
            }
        }
        if (!empty($indexes)) {
            foreach ($indexes as $name => $i) {
                if (!empty($string)) $string .= ',' . "\n";
                $string .= '   KEY `' . $name . '` (`' . implode('`, `', $i) . '`)';
            }
        }
        return $string;
    }

    /**
     * Get table information with minimal information, or more.
     * @vetted
     * @param $table string
     * @param $database string
     * @return array
     */
    public function structure($table, $database = ''){
        $additional = [];
        if ($database) {
            $sql = "SELECT column_name, data_type, character_set_name, collation_name FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE table_schema = '$database' AND table_name = '$table'";
            $result = $this->cnx->query($sql);
            foreach ($result->getResultArray() as $v) {
                $additional[$v['column_name']] = [
                    'data_type' => $v['data_type'],
                    'character_set_name' => $v['character_set_name'],
                    'collation_name' => $v['collation_name'],
                ];
            }
        }

        $structure = [];
        $sql = 'EXPLAIN `' . ($database ? $database . '`.`' : '') . $table . '`';
        $result = $this->cnx->query($sql);

        foreach($result->getResultArray() as $v){
            $a = [];
            $field = $v['Field'];
            $e = explode('(', $v['Type']);

            $a['name'] = $field;
            $a['type'] = $e[0];
            $a['max_length'] = !empty($e[1]) ? str_replace(')', '', $e[1]) : '';
            $a['default'] = $v['Default'];
            $a['primary_key'] = isset($v['Key']) && $v['Key'] === 'PRI' ? 1 : 0;

            if(substr($v['Type'], 0, 4) === 'enum' || $v['Type'] === 'set'){
                $enum = explode("','", substr($v['Type'], 6, strlen($v['Type']) - 8));
                $data_range = [];
                foreach($enum as $w){
                    $data_range[$w] = $w;
                }
                $a['data_range'] = $data_range;
                if($v['Type'] === 'set'){
                    $a['set'] = true;
                }
            }
            if(preg_match('/('.$this->genericSqlNumberFields.')\(/i', $v['Type'])){
                $a['unsigned'] = stristr($v['Type'], 'unsigned') !== false;
            }
            if(preg_match('/\(([0-9]+),([0-9]+)\)/', $v['Type'], $m)){
                $a['decimal'] = $m[2];
            }
            if(in_array($v['Type'], ['datetime', 'date', 'time', 'timestamp'])){
                $a['intent'] = $v['Type'];
            }
            if(!empty($additional[$field])) {
                $a['character_set_name'] = $additional[$field]['character_set_name'];
                $a['collation_name'] = $additional[$field]['collation_name'];
            }

            $structure[$field] = $a;
        }

        return $structure;
    }

    /**
     * @new
     * @created 2020-12-24
     *
     * @param $id
     */
    public function assign_key($id) {
        $table_key = $this->generateTableKey();
        $this->cnx->query("UPDATE sys_data_object SET table_key = '$table_key' WHERE id = $id AND table_key IS NULL");
    }

    public function query_builder($request, $structure, $meta) {
        /**
         * Function query_builder: cross-references a request with a table configuration
         *
         * Note that when we want to display data we are normally showing table fields but also values from JOINed tables, or values from calculated fields.  However, searching on any of the calculated fields, unless it is a view, is not as easy since you'd have something like this in the query:
         *
         *      &CONCAT(first_name,' ',last_name)=|like|onas Salk       (Jonas Salk)
         *
         * since that part left of the = sign doesn't work very well mapping to a configuration.
         *
         * Generally this is handled through ACL layers since it's also really hard to police a left-hand side expression like that above.
         *
         * Factors to consider:
         * --------------------
         * can the user search on this field
         * will the requested value result in anything anyway (searching non-date on date, alpha on int/float, etc)
         * should it be equal or like
         * can I convert the value to something that would make sense, and FTM should I do it.
         *
         */
        $where = '';
        $token = md5(time().rand());
        $conjunction = empty($meta['or']) ? 'AND' : 'OR';

        //2018-10-27: handle exclusive multiples, e.g. WHERE (Hostname LIKE '%abc%' AND Hostname LIKE '%def%')
        $exclusive_multiples = [];
        if(!empty($request['_exclusive_multiples'])){
            if(is_array($request['_exclusive_multiples'])){
                $exclusive_multiples = $request['_exclusive_multiples'];
            }else{
                $exclusive_multiples = explode(',', $request['_exclusive_multiples']);
            }
        }

        if(!empty($this->dataGroup['aliases'])){
            foreach($this->dataGroup['aliases'] as $alias => $v){
                $structure[$alias] = (is_array($v) ? $v : [
                    'expression' => $v,
                ]);
                $structure[$alias]['alias'] = true;
            }
        }

        foreach($structure as $field => $config){

            // handle relationship joins, name the joined field or expression and the needed joined table
            if (!empty($request['_relations'][$field]) &&
                $relation = $this->selectFieldRelation($request['_relations'][$field])
            ) {

                // we assume that the value is expressed in the selected relation
                $tableAlias = 't' . (count($this->joins) + 1);

                $this->joins[] = [
                    'alias' => $tableAlias,
                    'table' => $this->actualTableName($this->fetchDataGroup($relation['identifier'])->rootDataObject),
                    'on' => [
                        [
                            'field' => $field,
                            'root_alias' => 'r',
                        ],
                    ],
                ];
                $expressedField = $this->convertStringExpressionToJoinedTable($relation['label'], $tableAlias);
            } else {
                $expressedField = !empty($config['alias']) ? $config['expression'] : $field;
            }

            // Note: we can also handle arrays (non-associative)
            if(
                isset($request[$field]) &&
                (
                    ( is_array($request[$field]) && !empty($request[$field]))
                    ||
                    (!is_array($request[$field]) && !empty($request[$field]) && !empty(trim($request[$field])))
                )
            ){
                // run process below
            }else{
                continue;
            }

            $values = is_array($request[$field]) ? $request[$field] : [$request[$field]];
            $multiple = count($values) > 1;


            //note only "solid" field values get prepended table_alias
            $prefix = (
                !empty($config['table_alias']) && preg_match('/^' . $this->whole_field . '$/', $expressedField) ?
                    $config['table_alias'].'.' :
                    ''
            );

            if($multiple) $where .= ($where ? ' ' . $conjunction . ' ' : '') . '(';

            $i = 0;
            foreach($values as $value){
                $i++;

                $value = trim($value);

                if(substr($value,0,1) === '|'){
                    $value = substr($value,1);
                    $value = str_replace('\|', $token, $value);
                    $value = explode('|', $value);
                    foreach($value as $n => $v){
                        $value[$n] = str_replace($token, '|', $v);
                    }
                    //format is relationship|value1[|value2|value3 etc..]

                    //simply means we don't search on this
                    //2018-10-21 <sfullman@presidio.com> I am not sure this is used in the code..
                    //if(strtolower($value[0] === 'null')) continue;

                    if($multiple){
                        $conj = in_array($expressedField, $exclusive_multiples) ? 'AND' : 'OR';
                    }else{
                        $conj = $conjunction;
                    }
                    $where .= ($where && !($multiple && $i === 1) ? ' ' . $conj .' ':'');
                    $where .= $prefix . $expressedField;

                    if(strtolower($value[0]) === 'between') {
                        if (strlen(trim($value[1])) && strlen(trim($value[2]))) {
                            $where .= ' BETWEEN \'' . str_replace("'", '\\\'', $this->prepare_correct_format($config, $value[1])) . '\' AND \'' . str_replace("'", '\\\'', $this->prepare_correct_format($config, $value[2])) . '\'';
                        } else if (strlen(trim($value[1]))) {
                            $where .= ' >= \'' . str_replace("'", '\\\'', $this->prepare_correct_format($config, $value[1])) . '\'';
                        } else if (strlen(trim($value[2]))) {
                            $where .= ' <= \'' . str_replace("'", '\\\'', $this->prepare_correct_format($config, $value[1])) . '\'';
                        } else {
                            //no filter required
                        }
                    }else if(strtolower($value[0]) === 'in' || strtolower($value[0]) === 'notin') {
                        // IN(a, b, ..) or NOT IN(a, b, ..)
                        $rlx = strtolower($value[0]);
                        $str = $rlx === 'in' ? ' IN' : ' NOT IN';
                        $str .= '(';
                        for ($i = 1; $i < count($value); $i++) {
                            $val = $value[$i];
                            if (is_null($val)) {
                                $str .= 'NULL, ';
                            } else {
                                $str .= '\'' . str_replace("'", '\\\'', $this->prepare_correct_format($config, $val)) . '\', ';
                            }
                        }
                        $str = rtrim($str, ', ');
                        $str .= ')';
                        $where .= $str;
                    }else if(strtolower($value[0]) === 'is') {
                        if (is_null($value[1])) {
                            $where .= ' IS NULL ';
                        } else {
                            $where .= ' = ' . '\'' . str_replace("'", '\\\'', $this->prepare_correct_format($config, $value[1])) . '\'';
                        }
                    }else if(strtolower($value[0]) === 'null'){
                        $where .= ' IS NULL';
                    }else if(strtolower($value[0]) === 'notnull'){
                        $where .= ' IS NOT NULL';
                    }else if(strtolower($value[0]) === 'blank'){
                        //back-enter a left parenthesis
                        $where = substr($where, 0, strlen($where) - strlen($prefix . $expressedField)) . '(' . $prefix . $expressedField;
                        $where .= ' IS NULL OR ' . $prefix . $expressedField . ' = \'\')';
                    }else if(strtolower($value[0]) === 'notblank'){
                        //back-enter a left parenthesis
                        $where = substr($where, 0, strlen($where) - strlen($prefix . $expressedField)) . '(' . $prefix . $expressedField;
                        $where .= ' IS NOT NULL AND ' . $prefix . $expressedField . ' != \'\')';
                    }else if(in_array(strtolower($value[0]), array_keys($this->comparators))){
                        $rlx = $this->comparators[strtolower($value[0])];
                        //remember you can't compare with NULL, gt, lt, etc. don't make any sense so the user will get no results, but we can intervene for not equal - let's change it to IS NOT
                        if(is_null($value[1]) && $rlx === '!='){
                            $where .= ' IS NOT NULL';
                        }else{
                            $where .= $rlx . '\'' . str_replace("'", '\\\'', $this->prepare_correct_format($config, $value[1])) . '\'';
                        }
                    }else{
                        // no action yet
                        exit('unrecognized relationship key');
                    }
                }else{
                    if($multiple){
                        $conj = in_array($expressedField, $exclusive_multiples) ? 'AND' : 'OR';
                    }else{
                        $conj = $conjunction;
                    }
                    $where .= ($where && !($multiple && $i === 1)? ' ' . $conj .' ':'');
                    $where .= $prefix . $expressedField;
                    $where .= ' LIKE \'%' . str_replace("'", '\\\'', $value) . '%\'';
                }
            }
            //close parenthesis on field group
            if($multiple) $where .= ')';
        }
        return $where;
    }

    public function order_by_builder($orderBy, $structure = []){
        //OK formats
        // string:  Department ASC, Priority DESC
        // pipes:   |department|priority            (all work OK)
        //          |department|priority|desc
        //          |department|asc|priority|desc
        // array: [ department, priority => desc ]  (associative or not is OK)

        if(empty($orderBy)) return '';

        if(!is_array($orderBy)){
            // Here we have a |field|ASC|field2|DESC format.  Fields will be checked against $structure below
            if(substr($orderBy,0,1) === '|'){
                $token = md5(time().rand());
                $order = 'ASC';

                $orderBy = substr($orderBy,1);
                $orderBy = str_replace('\|', $token, $orderBy);
                $orderBy = explode('|', trim($orderBy, '|'));

                if(empty($orderBy)) return '';

                $a = []; $previous_key = '';
                foreach($orderBy as $v){
                    if(preg_match('/^(BIN)*(ASC|DESC)$/i', $v)){
                        $order = strtoupper($v);
                        if(isset($a[$previous_key])) $a[$previous_key] = $order;

                        //reset to default
                        $order = 'ASC';
                        continue;
                    }
                    $previous_key = str_replace($token, '|', $v);
                    $a[$previous_key] = $order;
                }
                $orderBy = $a;
            }else{
                // ORDER BY statements are too potentially complex to always be evaluated successfully, however this will get
                // most passed statements
                if(!empty($structure) && preg_match('/^((BINARY\s+)*([_a-z][_a-z0-9]*)(\s+(ASC|DESC))*,\s*)*$/i', trim($orderBy, ',') . ',')){
                    $a = preg_split('/,\s*/', $orderBy);
                    foreach($a as $field){
                        $stop = true;
                        $field = preg_replace('/^BINARY\s+/i', '', $field);
                        $field = preg_replace('/\s+(ASC|DESC)/i', '', $field);
                        foreach($structure as $n => $v){
                            if(strtolower($n) === strtolower($field)){
                                $stop = false;
                                break;
                            }
                        }
                        if($stop) break;
                    }
                    if($stop){
                        //log "parsable ORDER BY statement but unrecognized field(s)"
                        log_message('error', 'parsable ORDER BY statement "' . $orderBy . '" but unrecognized field(s)');
                        return '';
                    }
                    return $orderBy;
                }else{
                    //log "unparsable ORDER BY statement assumed good"
                    log_message('info', 'unparsable ORDER BY statement assumed good: "' . $orderBy . '"');
                }
                return $orderBy;
            }
        }
        $str = '';
        $fieldMap = [];
        $defaultPath = [new IntKey, 'handle'];
        $joins = $this->sArray($this->joins);
        $joinFieldPath = [new IntKey, 'on', new IntKey, 'field'];
        foreach($structure as $field => $config){
            $fieldMap[strtolower($field)] = $config;
        }
        foreach($orderBy as $n => $v){
            //make sure that "solid" fields that are not part of structure are omitted
            //note this will not filter out expressions like CONCAT(FirstName, ' ', LastName)
            //@todo, this could be done earlier or better
            if(
                count($fieldMap) &&
                preg_match('/^[a-z0-9_]+$/i', is_numeric($n) ? $v : $n) &&
                empty($fieldMap[strtolower(is_numeric($n) ? $v : $n)])
            ){
                continue;
            }

            // If relations are present for this field, replace with the requested label
            // Admittedly this is a crazy set of commands but if it never fails regardless of user input, that is a plus
            if (
                ($label = $this->sVal(
                    $this->request->_relations->{$n}->fetchSection($defaultPath, 'default', 2, SaasArray::RELATIONSHIP_VALUE)->label)
                ) &&
                ($alias = $this->sVal(
                    $joins->fetchSection($joinFieldPath, $n, 2, SaasArray::RELATIONSHIP_VALUE)->alias)
                )
            ) {
                // hack-alert: fix this
                $label = preg_replace('/\br\./', $alias . '.', $label);
                $str .= ($str ? ', ' : '') . (is_numeric($n) ? $v : (stristr($v, 'BIN') ? 'BINARY ' : '') . $label . ' ' . str_replace('BIN', '', $v));
            } else {
                $str .= ($str ? ', ' : '') . (is_numeric($n) ? $v : (stristr($v, 'BIN') ? 'BINARY ' : '') . $n . ' ' . str_replace('BIN', '', $v));
            }
        }
        return $str;
    }

    public function prepare_correct_format($config, $value){
        if(!empty($config['type']) && preg_match('/^(date|time|datetime)$/', $config['type'])){
            if(!strlen($value)){
                if(is_null($config['default'])){
                    return null;
                }
            }else{
                if(!preg_match($this->dateTimeFormats[$config['type']][0], $value)){
                    $convert = date($this->dateTimeFormats[$config['type']][1], strtotime($value));
                    if($convert === false){
                        //we are reasonably sure this is not going to go into the field
                    }else{
                        return $convert;
                    }
                }
            }
        }
        if(!empty($config['intent'])){
            if($config['intent'] === 'datetime'){
                if(preg_match('/int/', $config['type'])){
                    //make sure the format is also bigint
                    if(!is_numeric($value)){
                        return strtotime($value);
                    }
                }
            }
        }
        if(!empty($config['type']) && preg_match('/int|dec|decimal|float/', $config['type'])){
            if(is_null($value)) return $value;
            // /!\ NOTE: this is a temporary measure; dependending on user's locale, the role of , and . might be reversed.
            // todo: interpret this then through user's locale
            $convert = preg_replace('/[$£€,]/', '', $value);
            return $convert;
        }
        return $value;
    }

    public function array_to_json_in_field($array){
        $array = addslashes_deep($array);
        $json = json_encode($array);
        $json = preg_replace('/[\n\r]/', "", $json);
        $json = str_replace("\\'", "'", $json);
        return $json;
    }

    public function pre_process($mode, $request, $changes){
        $dataGroup = $this->dataGroup;
        if(empty($dataGroup['pre_process'])) return true;           //no preprocessing needed
        foreach($dataGroup['pre_process'] as $modes => $m){
            if(in_array($mode, explode(',', str_replace(' ', '', $modes)))){
                //invoke this method
                if(!preg_match('/^[\\\\_a-z0-9]+:[_a-z0-9]+$/i', $m)) exit('Error: improper preprocessing method call in dataGroup');
                $m = explode(':', $m);

                //handle peculiarities with CodeIgniter's model loading
                $this->load->model(str_replace('\\', '/', $m[0]));

                //without namespacing, all classes are in the root
                $class = explode('\\', $m[0]);
                $class = $class[count($class) - 1];

                $action = null;
                eval('$action = new \\' . $class . '();');
                $method = $m[1];
                // todo: we want to log on our end how long this took
                $response = $action->$method($mode, $request, $changes);

                //error from preprocess
                if(!empty($action->error)) {
                    $this->pre_process_error = $action->error;
                }
                if(!empty($action->comment)){
                    $this->pre_process_comment = $action->comment;
                }

                //should be true or false
                return $response;
            }
        }
        return true;
    }

    public function validate_rules($dataGroup){
        /* this function processes validate rules and validate_by_rule.  validate trumps (this is 2018; no pun intended) */

        $validation = [];

        if(!empty($dataGroup['validate_by_rule'])){
            foreach($dataGroup['validate_by_rule'] as $rule_raw => $fields){
                $rule_raw = explode(':', $rule_raw);
                $rule = $rule_raw[0];
                unset($rule_raw[0]);
                $params = implode(':', $rule_raw);
                foreach($fields as $field){
                    $validation[$field][$rule] = strlen($params) ? $params : true;
                }
            }
        }
        if(!empty($dataGroup['validate'])){
            foreach($dataGroup['validate'] as $field => $rules){
                if(is_array($rules)){
                    foreach($rules as $rule => $param){
                        $validation[$field][$rule] = $param;
                    }
                }else{
                    $validation[$field][$rule] = true;
                }
            }
        }
        return $validation;
    }

    public function security_access($dataGroup){
        if(empty($dataGroup['security']['access'])) return []; //no security defined

        $security = [];
        foreach($dataGroup['security']['access'] as $modes => $call){

            // we have security specified
            $call = explode(':', $call);
            if(count($call) === 1){
                $call[1] = $call[0];
                $call[0] = 'Security_model';
            }
            $call[1] = explode('|', $call[1]);
            if(!isset($call[1][1])) $call[1][1] = '';
            $call[1][1] = explode(',', $call[1][1]);

            $class = $call[0];
            $method = $call[1][0];
            $params = $call[1][1];
            $param_string = '$dataGroup, $modes, $security';
            foreach($params as $param){
                if(!$param) continue;
                $param_string .= ', $' . ltrim($param, '$');
            }

            //build the call
            try{
                eval('$sec = new \\' . $class . ';');
                eval('$sec_result = $sec->' . $method . '(' . $param_string . ');');
            }catch(\Exception $exception){
                exit('Error calling security script: '. $exception->getMessage());
            }

            if(!empty($sec_result)) $security = $sec_result;
        }
        return $security;
    }

    public function find_minimum_fitting_structure($file, $config = []){
        /**
         * Currently this function returns the highest level structure (high being int, date or time, or char, low always being text) based on the data.
         * If we wanted to calculate outliers (for example 99% of the fields were clean dates and 1% were null, blank or nonstandard text values), we'd need to rework the algorithm considerably.
         *
         * @todo: handle LONGTEXT and MEDIUMTEXT
         * @todo: handle decimal lengths
         * @todo: handle integer sizes
         * @todo: include value list for relatively small range of values (e.g. Low, Medium, High or 0 and 1)
         */

        $start = microtime(true);

        $string_buffer = (isset($config['string_buffer']) ? $config['string_buffer'] : 0.1);
        //left and right side of the decimal
        //ie. 2 places more than max value
        $float_place_buffer = (isset($config['float_place_buffer']) ? $config['float_place_buffer'] : 2);

        //default zero means 1, 2.5 and 2.75 would have 2 places; changing to 1 would produce 3 places i.e. 1.000, 2.500, 2.750
        $mantissa_place_buffer = (isset($config['mantissa_place_buffer']) ? $config['mantissa_place_buffer'] : 0);

        $date_microtimes = (isset($config['date_microtimes']) ? $config['date_microtimes'] : false);
        $sample_size = (isset($config['sample_size']) ? $config['sample_size'] : 1000);
        $fit_integers_tightly = (isset($config['fit_integers_tightly']) ? $config['fit_integers_tightly'] : true);


        $fp = fopen($file, 'r');
        $i = 0;
        while($row = fgetcsv($fp)){
            $i++;
            if($i === 1){
                // $headers = $row;
                foreach($row as $n=>$v){
                    $structure[$n] = [
                        'name' => $v,
                        'null' => null,         //ironic, i.e. we don't know
                        'type' => null,         //i.e. undefined
                        'length' => null,
                        'signed' => null,
                        '_maxlength' => 0,
                        '_decimal' => 0,
                    ];
                };
                continue;
            }
            //now build structure data
            foreach($row as $n => $v){

                // I believe the cap "\N" is a standard for null for "CSV for Microsoft Excel"
                if($v === '\N' || $v === '\n') $row[$n] = $v = null;

                //presence of null
                if(!($structure[$n]['null']) && is_null($v)) $structure[$n]['null'] = true;

                //do not let blank values determine structure
                if(!strlen($v)) continue;

                if(!(isset($structure[$n]['values']) && count($structure[$n]['values']) >= 31)){
                    if(empty($structure[$n]['values'][strtolower($v)])){
                        $structure[$n]['values'][strtolower($v)] = 1;
                    }else{
                        $structure[$n]['values'][strtolower($v)]++;
                    }
                }

                //no further processing with text
                //@todo add longtext etc. but doubtful need for CSV
                if($structure[$n]['type'] === 'text') continue;

                //assume text that long can't mean anything else..
                //@todo BTW this should probably be an exponential calc; the longer the values become, the amount of buffer should grow exponentially
                $structure[$n]['_maxlength'] = max($structure[$n]['_maxlength'], ceil(strlen($v) * (1 + $string_buffer)));
                if(strlen($v) * (1 + $string_buffer) > 255){
                    $structure[$n]['type'] = 'text';
                    continue;
                }

                //current value type
                if(is_numeric($v)){
                    if(substr($v,0,1) === '-') $structure[$n]['signed'] = true;
                    if(strstr($v, '.') && substr($v, -1) !== '.'){
                        $_type = 'decimal';
                        $structure[$n]['_decimal'] = max($structure[$n]['_decimal'], strlen(substr($v, strpos($v, '.') + 1)));
                    }else{
                        $_type = 'int';                     //don't distinguish for now between bigint, tinyint, etc.
                    }
                }else if(strtotime($v) !== false || ($date_microtimes && strtotime(preg_replace('/\.[0-9]*$/', '', $v)) !== false)){
                    if($date_microtimes) $v = preg_replace('/\.[0-9]*$/', '', $v);
                    $_type = date_time_component($v);
                }else{
                    $_type = strlen($v) * (1 + $string_buffer) > 255 ? 'text' : 'char';
                }

                // handle progressive structure widening
                switch($structure[$n]['type']){
                    case null:
                        //initialize type
                        $structure[$n]['type'] = $_type;
                        break;
                    case 'date':
                    case 'time':
                    case 'datetime':
                        if($_type !== 'date' && $_type !== 'time' && $_type !== 'datetime'){
                            $structure[$n]['type'] = 'char';
                        }else if($structure[$n]['type'] !== $_type){
                            //generalize it in date family
                            $structure[$n]['type'] = 'datetime';
                        }
                        break;
                    case 'int':
                        if($_type === 'decimal'){
                            $structure[$n]['type'] = 'decimal';
                        }else{
                            $structure[$n]['type'] = 'char';
                        }
                        break;
                    default:
                        // retain structure type
                }
            }
            //$rows[] = $row;
            if($sample_size && $i >= $sample_size) break;
        }
        fclose($fp);
        return ['structure' => $structure, 'elapsed' => round(microtime(true) - $start, 5)];
    }

    /**
     * @param null $tableOrKey
     * @param bool $override
     * @return mixed|null
     */
    public function loadAccountTables($tableOrKey = null, $override = false) {
        if (!$this->dataObjects || $override) {
            $this->dataObjectKeys = [];
            $this->dataObjects = [];
            $result = $this->cnx->query("SELECT * FROM sys_data_object");
            if ($a = $result->getResultArray()) {
                foreach ($a as $v) {
                    $this->dataObjects[strtolower($v['table_name'])] = $v;
                    $this->dataObjectKeys[strtolower($v['table_key'])] = $v['table_name'];
                    $this->dataObjectIds[$v['id']] = strtolower($v['table_name']);
                }
            }
        }

        if (is_int($tableOrKey)) {
            return $this->dataObjects[$this->dataObjectIds[$tableOrKey]];
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
     * @param $subdomain
     * @return bool
     */
    public function validateUserAccessToTable($table, $subdomain) {
        // ------- redundant for here but we do it anyway -------
        if (empty($_SESSION['login'])) return false;

        $login = $_SESSION['login'];
        if (empty($login['active'])) return false;
        if (empty($login['accounts'][$subdomain])) return false;
        // ------------------------------------------------------

        if (!$table) {
            return false;
        }

        /* todo: we should also check the user role LIVE for each API action; for now just check user session role */
        $rank = max(array_keys($login['accounts'][$subdomain]['roles']));

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
        return $tableRow['table_name'];
    }

    /**
     * @return string
     */
    protected function generateTableKey() {
        $str = strtolower(chr( 64 + rand(1, 26)));
        $str .= strtolower(chr( 64 + rand(1, 26)));
        $str .= rand(1234, 9999);
        $str .= strtolower(chr( 64 + rand(1, 26)));
        $str .= strtolower(chr( 64 + rand(1, 26)));
        return $str;
    }

    /**
     * Give a setting of multiple possible relation arrays for a given column select the best one based on user preferences
     * and locale preferences.  Allows for the best dataset of id, label and other meta information for the FE to present
     * in replacement for the foreign key field.
     *
     * This is checked thoroughly as it's user-creatable
     *
     * @param $relations
     * @param array $config
     * @return array|bool
     */
    public function selectFieldRelation($relations, $config = []){

        // This is intended so that for example an admin can configure multiple data relations for a field and
        // a non-admin can simply select a handle for best fit.
        $handle = 'default';

        if (!is_array($relations)) {
            return false;
        }
        $selectees = [];

        // Select which relation is best for mapping data to field
        foreach ($relations as $relation) {
            if (count($relations) === 1) {
                // selected by default
                break;
            }
            if (!empty($relations['handle']) && strtolower($relations['handle']) === strtolower($handle)) {
                // selected by declaring handle = default
                break;
            }
            $selectees[] = $relation;
        }

        if (!empty($selectees)) {
            $relation = $selectees[0];
        }

        // Validate selectee - identifier and label are required
        // todo: replace !is_string() with an actual parser of what the user could send
            // sys:sys_data_object          table group and table name
            // ec1234ab                     table key
            // financial_client             literal table name when keyed as such

        if (empty($relation['identifier']) || !is_string($relation['identifier'])) {
            // the identifier doesn't map to an actual data object
            return false;
        }
        if (empty($relation['label']) || !is_string($relation['label'])) {
            return false;
        }
        return $relation;
    }

    /**
     * Security feature, uses alias and not table
     *
     * @param $joins
     * @param array $config
     * @return string
     */
    protected function joinsToSQLString($joins, $config = []) {
        /*
         * Example of format:
        $joins = [
            [
                'type' => 'LEFT',                   // default if not specified
                'table' => 'actual_table_name',
                'alias' => 't2',
                'on' => [
                    [
                        'root_alias' => 'r',
                        'field' => 'table_id',
                        'rlx' => '=',               // default if not specified
                        'key' => 'id'
                    ]
                ],
            ]
        ];

        */
        if (empty($joins)) return '';

        $string = PHP_EOL;
        foreach ($joins as $join) {

            $string .= ($join['type'] ?? 'LEFT') . ' JOIN ' . $join['table'] . ' ' . $join['alias'] . ' ON ';
            $ons = [];
            foreach ($join['on'] as $on) {
                $ons[] = $on['root_alias'] . '.' . $on['field'] . ' ' .($on['rlx'] ?? '=') . ' ' . $join['alias'] . '.' . ($on['key'] ?? 'id');
            }
            $string .= implode(' AND ', $ons) . PHP_EOL;
        }

        return $string;
    }

    /**
     * Converts a string e.g. CONCAT(r.firstName, ' ', r.lastName) to CONCAT(t2.firstName, ' ', t2.lastName)
     *
     * @param $expression
     * @param $tableAlias
     * @return mixed
     */
    protected function convertStringExpressionToJoinedTable($expression, $tableAlias) {
        // todo: this needs to be configurable
        $rootTableAlias = 'r';
        $expression = preg_replace('/\b' . $rootTableAlias . '\.(' . $this->whole_field . ')/', $tableAlias . '.$1', $expression);
        return $expression;
    }

    protected $joins = [];

    public $whole_field = '[_a-zA-Z]+[_0-9a-zA-Z]*';

    /**
     * @param $request
     * @return mixed
     */
    public function export($request) {
        //parse front-end JavaScript settings
        $request['orderBy'] = empty($request['orderBy']) ? '' : json_decode($request['orderBy'], true);
        $columnsToShow = !empty($request['columnsToShow']) ? json_decode($request['columnsToShow'], true) : [];
        $share = !empty($request['share']) ? json_decode($request['share'], true) : [];

        $exportAs = !empty($share['exportAs']) ? $share['exportAs'] : '';

        if($exportAs !== 'link') $results = $this->request($request, ['limitOverride' => true]);

        $key = substr(md5(rand() . time()), 0, 8);

        if($exportAs === 'xlsx' || $exportAs === 'csv') {

            $file = $this->filePath . $exportAs . '-' . $key . '.' . $exportAs;

            $headings = [];
            foreach ($results['structure'] as $col => $null) {
                if (!empty($share['exportOnlyColumnsShown']) && !empty($columnsToShow) && !in_array($col, $columnsToShow)) continue;
                $headings[] = camelCase(snakeCase($col));
            }

            if($exportAs === 'xlsx'){
                if (!class_exists('ZipArchive')) {
                    // Error T13
                    throw new \App\Exceptions\GeneralFault(13);
                }

                if (!class_exists('Box\Spout\Writer\XLSX\Writer')) {
                    // Error T14
                    throw new \App\Exceptions\GeneralFault(14);
                }
                $writer = WriterFactory::create(Type::XLSX);
                $writer->openToFile($file); // write data to a file or to a PHP stream
                //$writer->openToBrowser($fileName); // stream data directly to the browser
                $writer->addRow($headings);
            } else {
                $output = [$headings];
                $fp = fopen($file, 'w');
            }

            if (!empty($results['dataset'])) {
                foreach ($results['dataset'] as $row) {
                    if (!empty($share['exportOnlyColumnsShown']) && !empty($columnsToShow)) {
                        foreach ($row as $col => $val) {
                            if (!in_array($col, $columnsToShow)) {
                                unset($row[$col]);
                                continue;
                            }
                            if (!empty($results['structure'][$col]['intent'])) {
                                if ($results['structure'][$col]['intent'] === 'datetime') {
                                    $row[$col] = createDate($val, $this->defaultDateFormatHuman);
                                }
                            }
                        }
                    }
                    if ($exportAs === 'xlsx') {
                        $writer->addRow($row);
                    } else {
                        $output[] = $row;
                    }
                }
            } else {
                $noRecordsRow = ['No records were found based on your criteria'];
                if($exportAs === 'xlsx') {
                    //write not-available row
                    $writer->addRow($noRecordsRow);
                } else {
                    $output[] = $noRecordsRow;
                }
            }

            if($exportAs === 'xlsx') {
                $writer->close();
            } else {
                fwrite($fp, array_to_csv($output));
                fclose($fp);
            }

        } else if($exportAs === 'link'){
            if (true) {
                exit('link not developed; it will be developed as part of a url shortening service in SAAS');
                $request['request'] = end($original);
                $request = json_encode($request);
                $settings = str_replace("'", "\\'", $request);
                $settings = str_replace("\\\"", "\\\\\"", $settings);
                $sql = "INSERT INTO master.share_links SET
            username = '". $_SESSION['UserName'] . "',
            public = ".(!empty($share['report']) && isset($share['public']) ? "'" . $share['public'] . "'" : 'NULL').",
            name = " . (!empty($share['report']) && !empty($share['reportName']) ? "'" . str_replace("'", "\\'", $share['reportName']) . "'" : 'NULL') . ",
            description = " . (!empty($share['report']) && !empty($share['reportDescription']) ? "'" . str_replace("'", "\\'", $share['reportDescription']) . "'" : 'NULL') . ",
            token = '$key',
            settings = '$settings'";
                $data->cnx->query($sql);


                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode(
                        [
                            'key' => $key,
                            'exportAs' => $exportAs,
                            'link' => get_client_url(['suppressPort' => true]) . '/api/share/link?' . $key,
                        ]
                    ));
            }
        }
        // todo: this could be either a table label or table key - standardize to the table_key value
        // todo: handle garbage collection
        $filename = 'Export_' . $request['request'] . '-' . date('Y-m-d@H-i-s') . '[' . count($results['dataset']) . '-records].' . $exportAs;
        return [
            'key' => $key,
            'exportAs' => $exportAs,
            'filename' => $filename,
        ];
    }

    /**
     * Return a data group definition by a unique string such as my-application, identifier such as ec1298gz, or id such as 7985
     * The latter id method is discouraged.  If an int id is passed, it MUST be the id in sys_data_group, not sys_data_object
     *
     * @param string $dataGroup
     * @param string $disposition
     * @param DataGroupDefinition|NULL $dataGroupDefinition
     * @return DataGroupDefinition
     */
    public function fetchDataGroup(string $dataGroup, $disposition = '', DataGroupDefinition $dataGroupDef = null, $skipInheritance = false) {
        /**
         * todo:
         *      do we set this in this class' state or not.
         * We can allow table_id to be present, or the table_id of the latest ancestor, OR the root table in any of that inheritance path
         */
        $key = $dataGroup;

        // Create an initial instance of the Definition
        $new = false;
        if ($dataGroupDef === null) {
            $new = true;
            $dataGroupDef = new DataGroupDefinition();
        }

        if ($disposition === 'table') {
            if ($dataGroupDef->rootDataObject) {
                // root data object (i.e. table) has been defined, take no action
                return $dataGroupDef;
            }

            if ($rootDataObject = $this->fetchDataObject($dataGroup)) {
                $dataGroupDef->rootDataObject = $rootDataObject;
            }

            // table fetch portion is now complete
            return $dataGroupDef;
        }


        $sql = "SELECT g.*, xref.child_object_type, xref.child_object_id
        FROM sys_data_group g 
        LEFT JOIN sys_data_group_xref xref ON g.id = xref.data_group_id AND xref.child_object_relationship = 'root table'
        WHERE '$key' IN (g.id, g.group_key, g.group_label)
        ";
        $query = $this->cnx->query($sql);
        if ($record = $query->getResultArray()) {
            if (count($record) > 1) {
                throw new GeneralFault(12, $dataGroup);
            }
            $record = $record[0];
            /*
             * we will have the following:
             * if table_id and a joined record we log an alert to what appears to be a misconfiguration
             * if joined record we either take it if a dataObject, or query again if a dataGroup
             *
             */

            if ($disposition === 'inheritance') {
                if (!$skipInheritance) {
                    $dataGroupDef->pushToInheritance($record);
                }
            } else {
                $dataGroupDef->setDataGroup($record);
            }

            // ------- value of bool processJoinedRootTableFirst determines which of these two run first ---------
            if (!$dataGroupDef->rootDataObject) {
                if (!$this->processJoinedRootTableFirst) goto processTableIdFirst;

                processTableIdFirstAlternate:
                if ($record['child_object_id']) {
                    if ($record['table_id']) {
                        // this appears to be a misconfiguration
                        // todo: log message
                    }

                    if ($record['child_object_type'] === 'sys_data_object') {
                        $dataGroupDef->rootDataObject = $dataGroupDef->rootDataObject ?? $this->loadAccountTables((int)$record['child_object_id']);
                    } else if ($record['child_object_type'] === 'sys_data_group') {
                        $dataGroupDef->rootDataObject =
                            $dataGroupDef->rootDataObject ?? $this->fetchDataGroup(
                            // we are going to pass as if this is a root request; the presence of the dataGroup and lack of `inheritance` will be enough cue.
                                $record['child_object_id'], null, $dataGroupDef, true
                            )->rootDataObject;
                    }
                }
                if (!$this->processJoinedRootTableFirst) goto afterProcess;

                processTableIdFirst:
                if ($record['table_id']) {
                    $dataGroupDef->rootDataObject =
                        $dataGroupDef->rootDataObject ?? $this->fetchDataGroup(
                            $record['table_id'], 'table', $dataGroupDef
                        )->rootDataObject;
                }
                if (!$this->processJoinedRootTableFirst) goto processTableIdFirstAlternate;

                afterProcess:
            }
            // ---------------------------------------------------------------------------------------------------
            if ($record['data_group_id']) {
                // We are checking for inheritance but skip it if we're ascending up a table_group branch for a joined root table data_group
                $dataGroupDef = $this->fetchDataGroup($record['data_group_id'], 'inheritance', $dataGroupDef, $skipInheritance);
            }
        }

        /**
         * todo:
         *  DONE    0. test with loadAccountTables
         *  DONE    00. turn DGD into a __call getter and setter based on type - do something cool, and this becomes the progenitor of my tooling class
         *  DONE    1. have non inheritance classes omitted
         *  DONE    2. experiment with the table value being in different places
         *  DONE    3. if I can't find anything in dataGroup and this is initial, see if I can find anything in sys_data_object
         *  4. finalize the rules for objects and lose the whole group concept for tables but keep it in terms of user-friendly organization
         *  5. finish all the way to the initial view() load and the api/data/select load and etc.
         *  6. is actualTableName affected?
         */

        if ($new && empty($dataGroupDef->rootDataObject)) {
            // one last try
            $dataGroupDef = $this->fetchDataGroup($dataGroup, 'table', $dataGroupDef);
        }
        return $dataGroupDef;
    }

    /**
     * Very important method, contains the logic for how a URI string can fetch a rootDataObject record
     * @param string $key
     * @return bool
     */
    public function fetchDataObject(string $key) {
        $sql = "SELECT t.* FROM sys_data_object t WHERE '$key' IN(t.id, t.table_name, t.table_key, t.table_label)";
        $query = $this->cnx->query($sql);
        if ($record = $query->getResultArray()) {
            return $record[0];
        }
        return false;
    }

    /**
     * @param $subject
     * @return SaasArray
     */
    public function sArray($subject, $config = []) {
        return new SaasArray($subject, $config);
    }

    public function sVal($value) {
        return SaasArray::value($value);
    }
}
