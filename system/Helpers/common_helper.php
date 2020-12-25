<?php

/**
 *
 * Common function helper file.
 *
 */

if(!function_exists('snakeCase')){
    function snakeCase($str){
        //todo: "How To Win Friends and Influence People" - library for handling the `and` here
        //todo: non-Latin-1 cases
        return ucwords(str_replace('_', ' ', $str));
    }
}

if(!function_exists('camelCase')){
    function camelCase($str){
        return preg_replace('/([a-z])([A-Z])/', '$1 $2', $str);
    }
}

if(!function_exists('createDate')){
    function createDate($val, $format = ''){
        if(!$format) $format = 'Y-m-d H:i:s';
        if($val === '' || $val === null) return $val;
        if(strstr(strtolower(gettype($val)), 'object')){
            //we are not dealing with PHP date objects yet
            return $val;
        }
        if(is_numeric($val)){
            //assume a UNIX timestamp
            return date($format, $val);
        }else if(strtotime($val) !== false){
            return date($format, strtotime($val));
        }
        return $val;
    }
}

