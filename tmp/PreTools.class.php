<?php

class PreTools {

    public static $display_errors;

    public static $display_startup_errors;

    public static $error_reporting;

    public static $path;

    public static function invokeStrict($config = [])
    {

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        return;

        self::$display_errors = ini_get('display_errors');
        self::$display_startup_errors = ini_get('display_startup_errors');
        self::$error_reporting = error_reporting();

        $errorsActive = [
            E_ERROR             => FALSE,
            E_WARNING           => FALSE,
            E_PARSE             => FALSE,
            E_NOTICE            => FALSE,
            E_CORE_ERROR        => FALSE,
            E_CORE_WARNING      => FALSE,
            E_COMPILE_ERROR     => FALSE,
            E_COMPILE_WARNING   => FALSE,
            E_USER_ERROR        => FALSE,
            E_USER_WARNING      => FALSE,
            E_USER_NOTICE       => FALSE,
            E_STRICT            => FALSE,
            E_RECOVERABLE_ERROR => FALSE,
            E_DEPRECATED        => FALSE,
            E_USER_DEPRECATED   => FALSE,
            E_ALL               => TRUE,
        ];
        $error_reporting = array_sum(
            array_keys($errorsActive, $search = true)
        );
        ini_set('display_errors', isset($config['display_errors']) ? $config['display_errors'] : 1);
        ini_set('display_startup_errors', isset($config['display_startup_errors']) ? $config['display_startup_errors'] : 1);
        error_reporting(isset($config['error_reporting']) ? $config['error_reporting'] : $error_reporting);

    }
    public static function cancelStrict()
    {
        if (self::$display_errors === null) {
            exit('cancelStrict() can only be called after invokeStrict has first been called');
        }
        ini_set('display_errors', self::$display_errors);
        ini_set('display_startup_errors', self::$display_startup_errors);
        error_reporting(self::$error_reporting);
    }

    public static function xlog($message, $config = [])
    {
        /**
         * Note, the path is by default for CI4
         */
        extract($config);
        isset($close) ? null : $close = true;
        isset($path) ? null : $path = realpath(dirname(__FILE__ . '../writable/logs/log-{Y-m-d}.php'));
        $path = self::xlogPathDynamic(self::$path);
        $there = file_exists($path);
        $fp = fopen($path, 'a');
        if(!file_exists($path)){
            //meh..
        }
        if(!$there){
            chmod($path, 0755);
        }
        fwrite($fp, '[' . date('Y-m-d g:i:sA') . '] - ' . print_r($message, 1) . PHP_EOL);
        //should I release this or not?
        if($close) fclose($fp);

    }

    /**
     * Modify as needed for your framework
     * @param $path
     * @return string
     */
    public static function xlogPathDynamic($path)
    {
        $path = str_replace('{Ymd}', date('Ymd'), $path);
        $path = str_replace('{Y-m-d}', date('Y-m-d'), $path);
        return $path;
    }
}

// If we're using this we probably want this level of reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


function pre($value, $config = []) {
    $die = $config === 'die' || $config == 1 || !empty($config['exit']);
    echo '<pre>';
    echo "\n";
    print_r($value);
    echo '</pre>';
    if ($die) die();
}

function xlog($message, $config = []) {
    PreTools::xlog($message, $config);
}
