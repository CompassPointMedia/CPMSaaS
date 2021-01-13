<?php
namespace App\Libraries;

/**
 * Class Tooling
 *
 * Just because I wanted to do something new for class management tools.
 * @todo: maybe make some of these traits
 *
 * @package App\Libraries
 * @author  Samuel Fullman
 *
 *
 */
class Tooling
{
    /**
     * @param $function
     * @param $arguments
     * @return bool|null
     */
    public function __call($function, $arguments) {

        if (isset($action));
        if (isset($method));

        extract($this->convertFunction($function));

        // work with attributes
        if ($action === 'set') {
            $this->$method = $arguments[0];
        } else if ($action === 'get') {
            return $this->$method ?? null;
        } else if ($action === 'pushto') {
            if ($this->$method === null) {
                $this->$method = [];
            }
            $this->$method[] = $arguments[0];
            return true;
        }
    }

    /**
     * @param $method
     * @return array
     */
    public function convertFunction($method) {
        if (substr(strtolower($method), 0, 6) === 'pushto') {
            $pre = 6;
            $action = 'pushto';
        } else {
            // get or set
            $pre = 3;
            $action = strtolower(substr($method, 0, 3));
        }
        $method = strtolower($method{$pre}) . substr($method, $pre+1);
        unset($pre);
        return get_defined_vars();
    }
}

