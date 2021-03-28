<?php
namespace App\Modules\InflectionPoint\Presentation;


class CVT
{
    /**
     * Connector to database containing config tables etc.
     * @var $cnx
     */
    private $cnx;

    private $startHash;

    private $endHash;

    private $encoding = false;


    public function __construct($cnx) {
        $this->cnx = $cnx;
        $this->startHash = md5(time().rand());
        $this->endHash = strrev($this->startHash);
    }

    /**
     *
     * @param $table
     * @param $fields
     * @param array $config
     * @return string
     */
    public function dataGroupJavascriptV1($table, $fields, $config = []) {
        if (false) { ?><script><?php }
        ob_start();
        // --------------------------------------------- ?>

// dynamically declared JS from method dataGroupJavascriptV1 at <?php echo date('Y-m-d g:i:sA') . PHP_EOL;?>
var requestURI = '/api/data/request/<?php echo $table?>',
	insertURI = '/api/data/insert/<?php echo $table?>',
    updateURI = '/api/data/update/<?php echo $table?>',
    deleteURI = '/api/data/delete/<?php echo $table?>',
    settings = {
        showDeleteDevice: true,
    },
    focus = {
        <?php foreach ($fields as $field => $data) { ?>
        <?php echo $field;?>: '',
        <?php } ?>
    };

		<?php	// ---------------------------------------------
        $output = ob_get_clean();
        if (false) { ?>
        // Inventory of variables declared above
        used(requestURI, insertURI, updateURI, deleteURI, settings, focus)
        </script><?php }

        // return the result string
        return rtrim($output) . PHP_EOL;
    }


    /**
     *
     * @param $table
     * @param $fields
     * @param array $config
     * @return string
     */
    public function dataGroupJavascriptV2($table, $fields, $config = []) {
        if (false) { ?><script><?php }
        ob_start();
        // --------------------------------------------- ?>

// dynamically declared JS from method dataGroupJavascriptV2 at <?php echo date('Y-m-d g:i:sA') . PHP_EOL;?>
var injector = {
	requestURI: '/api/data/request/<?php echo $table?>',
	insertURI: '/api/data/insert/<?php echo $table?>',
	updateURI: '/api/data/update/<?php echo $table?>',
	deleteURI: '/api/data/delete/<?php echo $table?>',
    shareURI: '/api/data',
	settings: {
		showDeleteDevice: true,
	},
	focus: {
        <?php foreach ($fields as $field => $data) { ?>
        <?php echo $field;?>: '',
        <?php } ?>
    }
};

        <?php	// ---------------------------------------------
        $output = ob_get_clean();
        if (false) { ?>
        var used = function(){
			// Show an inventory of variables declared above
        };
        used(injector.requestURI, injector.insertURI, injector.updateURI, injector.deleteURI, injector.settings, injector.focus);
        </script><?php }

        // return the result string
        return rtrim($output) . PHP_EOL;
    }

    /**
     * Generate merged global/user custom JavaScript for Data Object
     * @comment See comments inside method
     *
     * @param $table_key
     * @param array $settings
     * @return string
     */
    public function dataGroupJavascriptConfigV1($table_key, $settings = []) {
        /*
         * 2021-01-04 - Important: this is pulling configuration records on the table table.  It's not taking into account or even yet
         * responsible for calculating overrides from the user and the dataGroup.  Hang on for a wild ride when that happens, but if
         * done well will be a very usable system.
         */

        // todo: if any settings present, extract them
        $config = [];

        $dataObject = 'default';
        $query = $this->cnx->query("SELECT c.* FROM sys_data_object t JOIN sys_data_object_config c ON t.id = c.object_id AND c.object_type = 'sys_data_object'
        
        WHERE 
        t.table_key = '$table_key' AND
        data_object = '$dataObject'
        ");
        $results = $query->getResultArray();
        // todo: table_access - this is intended for who can modify the config bit, not really relevant here
        // todo: have JS functions not be entirely inside quotes and treated as string, use $methodHash


        foreach ($results as $a) {
            if (!$a['active']) {
                continue;
            }

            $value = $this->isCode($a['value'], $a['node'], $a['attribute']);

            switch (true) {
                // Grab this first
                case $a['node'] === 'lazy':
                    if (empty($config['lazy'])) {
                        $config['lazy'] = [ $value ];
                    } else {
                        $config['lazy'][] = $value;
                    }
                    break;
                case $a['item_type'] === 'configuration';
                    $config[$a['node']][$a['path']][$a['field_name']][$a['attribute']] = $value;
                    break;
            }
        }
        $configString = json_encode($config);
        if ($this->encoding) {
            $configString = $this->exposeStringObjectsInJson($configString);
        }
        $string = 'var sys_data_object_config = ' . (empty($config) ? '{}' : $configString) . ';' . PHP_EOL;
        return $string;
    }

    /**
     * @param $value
     * @param $node
     * @param $attribute
     * @return string
     */
    public function isCode($value, $node, $attribute) {
        if ($attribute === 'relation' || $node === 'lazy' || substr(trim($value), 0, 1) === '{' || substr(trim($value), 0, 1) === '[') {
            $this->encoding = true;
            return $this->startHash . $value . $this->endHash;
        }
        return $value;
    }

    /**
     * Turns eg { config: "[a,false,'my-string', function(){alert('hello');}]" } into
     *          { config: [a,false,'my-string', function(){alert('hello');}] }
     *
     * @param $configString
     * @return mixed
     */
    public function exposeStringObjectsInJson($configString) {
        preg_match_all('/"' . $this->startHash . '(.*)' . $this->endHash . '"/', $configString, $matches);
        foreach ($matches[0] as $match) {
            // do surgery to expose the code
            $str = str_replace('"' . $this->startHash, '', $match);
            $str = str_replace($this->endHash . '"', '', $str);
            $str = str_replace('\n', "\n", $str);
            $str = str_replace('\r', "\r", $str);
            $str = str_replace('\t', "\t", $str);
            $str = str_replace('\/', "/", $str);
            // this is probably very fallible but should work for most things. todo: figure out a backslash sequence like \\" that would break this
            $str = str_replace('\"', '"', $str);
            $configString = str_replace($match, $str, $configString);
        }
        return $configString;
    }
}