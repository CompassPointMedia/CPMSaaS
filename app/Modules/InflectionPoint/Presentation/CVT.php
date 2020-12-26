<?php
namespace App\Modules\InflectionPoint\Presentation;


class CVT
{
    /**
     * Connector to database containing config tables etc.
     * @var $cnx
     */
    private $cnx;

    public function __construct($cnx) {
        $this->cnx = $cnx;
    }

    /**
     *
     * @param $table
     * @param $fields
     * @param array $config
     * @return string
     */
    public function dataObjectJavascriptV1($table, $fields, $config = []) {
        if (false) { ?><script><?php }
        ob_start();
        // --------------------------------------------- ?>

// dynamically declared JS from method dataObjectJavascriptV1 at <?php echo date('Y-m-d g:i:sA') . PHP_EOL;?>
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
    public function dataObjectJavascriptV2($table, $fields, $config = []) {
        if (false) { ?><script><?php }
        ob_start();
        // --------------------------------------------- ?>

// dynamically declared JS from method dataObjectJavascriptV1 at <?php echo date('Y-m-d g:i:sA') . PHP_EOL;?>
var injector = {
	requestURI: '/api/data/request/<?php echo $table?>',
	insertURI: '/api/data/insert/<?php echo $table?>',
	updateURI: '/api/data/update/<?php echo $table?>',
	deleteURI: '/api/data/delete/<?php echo $table?>',
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
     * @param $table_key
     * @param array $settings
     * @return string
     */
    public function dataObjectJavascriptConfigV1($table_key, $settings = []) {

        // todo: if any settings present, extract them
        $config = [];

        $dataObject = 'default';
        $query = $this->cnx->query("SELECT c.* FROM sys_table t JOIN sys_table_config c ON t.id = c.table_id
        
        WHERE 
        table_key = '$table_key' AND
        data_object = '$dataObject'
        ");
        $results = $query->getResultArray();
        // todo: table_access - this is intended for who can modify the config bit, not really relevant here
        // todo: have JS functions not be entirely inside quotes and treated as string, use $methodHash

        $startHash = md5(time().rand());
        $endHash = strrev($startHash);
        $encoding = false;

        foreach ($results as $a) {
            if (!$a['active']) {
                continue;
            }

            switch (true) {
                // Grab this first
                case $a['node'] === 'lazy':
                    $encoding = true;
                    if (empty($config['lazy'])) {
                        $config['lazy'] = [ $startHash . $a['value'] . $endHash ];
                    } else {
                        $config['lazy'][] = $startHash . $a['value'] . $endHash;
                    }
                    break;
                case $a['item_type'] === 'configuration';
                    $config[$a['node']][$a['path']][$a['field_name']][$a['attribute']] = $a['value'];
                    break;
            }
        }
        $configString = json_encode($config);
        if ($encoding) {
            preg_match_all('/"' . $startHash . '(.*)' . $endHash . '"/', $configString, $matches);
            foreach ($matches[0] as $match) {
                // do surgery to expose the code
                $str = str_replace('"' . $startHash, '', $match);
                $str = str_replace($endHash . '"', '', $str);
                $str = str_replace('\n', "\n", $str);
                $str = str_replace('\r', "\r", $str);
                $str = str_replace('\t', "\t", $str);
                // this is probably very fallible but should work for most things. todo: figure out a backslash sequence like \\" that would break this
                $str = str_replace('\"', '"', $str);
                $configString = str_replace($match, $str, $configString);
            }
        }
        $string = 'var sys_table_config = ' . (empty($config) ? '{}' : $configString) . ';' . PHP_EOL;
        return $string;
    }
}