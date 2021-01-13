<?php
namespace App\Libraries;

/*
 *
 * so the SaasArray is an interesting thing but my critiques are:
 * 1. I really want $object['known_key'] and there are stack resources that say it's possible:
 *      https://stackoverflow.com/questions/7586746/can-i-overload-an-array-subscript-operator-in-php/7586771#7586771
 *      https://stackoverflow.com/questions/62875204/how-to-dynamically-specify-type-of-returnable-object-in-php7
 *      - and I owe it to the responses to experiment and see a working example
 * 2. I would like to make IntKey repeatable like 1+ times
 * 3. I would like to make StringKey matchable
 * 4. I will go with a global helper for sVal and sArray - but ideally a final [SaasArray::VAL] (really cool) or ->val() at the end should work
 * 5. this should definitely be released to the community
 * 6. remember that I wanted to do "exists" or "notExists" and also get a count or an average or things like I would look for from SQL - so __data should become __store and should have a subkey __root
 * 7. want to alter data as well so something like $saasData->mapKeys where certain keys in __root get mapped to more objects
 * 8. though I hadn't planned it, if this could simulate SQL operation closely enough, I'd take it as a sign I'm on a good track for being useful
 *
 * exit('where I left off');
 */


/**
 * Class SaasArray
 *
 * Some of the arrays (and perhaps moreso objects in the future) are complex, and a way to search them is going to be useful.
 * Some things I'd want to do are:
 *  1. return the value of a path (yeah that sounds suspiciously like XML but see comments on StringKey and IntKey matching
 *  2. verify a node exists
 *  3. return single or multiple with some type of meta information IN THE CLASS about where each one is
 *  4. by-reference CHANGE a value or delete it entirely
 *
 *  $fish->returnFirst()->getLike('salmon*');
 *
 * Note the use of StringKey and IntKey classes.  I want these to both probably extend a \Key class that can receive a constructor
 * argument for a pattern, and match against it, vs. only saying "yes this node on the path is int, or is string"
 *
 *         $result = saasArray($this->joins)->fetchSection([new IntKey(), 'on', new IntKey(), 'field'], 'object_id', 2)['alias'];
 *
 * todo: Obviously you might imagine that the IntKey declarations could be replaced by some global $skipIntKeys attribute, or maybe in some cases a way to declare "IntKey()* indefinite number of times"
 * todo: make this bulletproof in terms of calls,
 *      (new SaasArray($someArray))->doesNotExist should not crash
 * todo: pass something like this:
 *      $subject = new SaasArray($subject, SaasArray::ATTR_1, SaasArray::ATTR_2, etc.)
 *      allow either $config = [] or that, or a mixture.
 * todo: allow objects to be passed as well as arrays
 *      object types should be preserved and ideally returned when needed
 */
class SaasArray extends \ArrayObject {
    // todo: if it's already a saasObject just have it return itself

    const SAASARRAY_RETURN_AS_SELF = 1;
    const SAASARRAY_RETURN_AS_ARRAY = 2;
    const SAASARRAY_RETURN_AS_OBJECT = 3;
    const SAASARRAY_RETURN_AS_SAASARRAY = 4;
    const RELATIONSHIP_VALUE = 1;
    const RELATIONSHIP_FIELD = 2;

    public $__storage;

    public $__result;

    /**
     * Passthrough placeholder
     * @var array
     */
    public $__config = [];

    public $__unfound_attribute = false;

    public $__found_value = false;

    public $__return_as = self::SAASARRAY_RETURN_AS_SAASARRAY;

    public $findSingle = false;


    /**
     * SaasArray constructor.
     * @param array|null|object $array
     * @param array $config
     */
    public function __construct($array, $config = []) {
        if ($array instanceof self) {
            return $array;
        }
        if (!is_array($array)) {
            throw new \Exception('SaasArray only handles constructors of type `array`, you passed `' . gettype($array) . '`');
        }
        $this->__storage = $array;

        // Passthrough config values
        $this->__config = $config;

        // Set attribute values
        foreach ($config as $attribute => $value) {
            $attribute = $this->saasArrayArguments($attribute);
            $this->$attribute = $value;
        }
        parent::__construct($array);
    }

    /**
     * Convert humanFriendly camel case into SaasArray __human_friendly
     * Does not check if valid attribute
     *
     * @param $attribute
     * @return string
     */
    public function saasArrayArguments($attribute) {
        if (strstr($attribute, '__') === 0) return $attribute;
        if (preg_match('/[a-z][A-Z]/', $attribute)) {
            return '__' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $attribute));
        }
        return $attribute;
    }

    /**
     * @param $hierarchy
     * @param $value
     * @param $index
     * @param string $relationship
     * @return SaasArray|null|\stdClass
     */
    public function fetchSection($hierarchy, $value, $index, $relationship = 'field') {
        $criteria = 'exists';

        // unfortunately see first user comment on php docs, upvoted 240 times :(
        // array_walk_recursive( $this->subject , [$this,'traverse'] , $userdata = 'go' );
        // pre('done');

        $identifier = md5(rand());
        $ancestorArrays = [$identifier => $this->__storage];

        // Initialize result to null
        $this->__result = ($this->__return_as === self::SAASARRAY_RETURN_AS_SAASARRAY ? new SaasArray([], $this->__config) : null);

        $this->traverse($this->__storage, $hierarchy, $value, $index, $relationship, 0, $ancestorArrays);

        /**
         * Note, that at this point ->__result is set.  We don't destroy it, so it could be accessed later if desired
         */
        switch ($this->__return_as) {
            case self::SAASARRAY_RETURN_AS_SAASARRAY:
                return new SaasArray($this->__result);
            case self::SAASARRAY_RETURN_AS_SELF:
                return $this->__result;
            case self::SAASARRAY_RETURN_AS_OBJECT:
                return $this->arrayToObjectDeep($this->__result);
        }
        exit('Unspecified return type in SaasArray::fetchSection');
    }

    public function fetchSectionArray() {

    }

    /**
     * Inline method to clear the result store
     * @return SaasArray
     */
    public function clearResult() {
        $this->__result = null;
        return $this;
    }

    public function __call($name, $arguments) {
        // todo: implement __call
    }

    public static function value($value) {
        if ($value instanceof SaasArray) {
            if (!empty($value->__unfound_attribute)) {
                return NULL;
            } else if (!empty($value->__found_value)){
                return $value->__storage['__found_value'];
            } else {
                return $value->__storage;
            }
        }
        return $value;
    }

    public function __get($name) {
        // handle cascaded unfound attributes
        if ($this->__unfound_attribute) {
            // todo: if there's a logging service present, log the fact for developer notification
            // return null;
            return $this;  // passthrough indefinitely?
        }

        if (!isset($this->__storage[$name])) {
            $empty = [];
            $config = array_merge($this->__config, ['__unfound_attribute' => true]);
            return new SaasArray($empty, $config);
        }
        if (is_array($this->__storage[$name])) {
            // here is where I'd like to know whether to return that array or an actual
            return new SaasArray($this->__storage[$name], $this->__config);
        }
        // store found value in special key - todo: method of just returning the value instead needed
        $config = array_merge($this->__config, ['__found_value' => true]);
        return new SaasArray(['__found_value' => $this->__storage[$name]], $config);
    }

    /**
     * @param $subject
     * @param $hierarchy
     * @param $value
     * @param $index
     * @param $relationship
     * @param $level
     * @param $ancestorArrays
     */
    public function traverse(&$subject, $hierarchy, $value, $index, $relationship, $level, $ancestorArrays) {
        foreach ($subject as $key => $object) {
            if ($onPath = $this->negotiate($hierarchy, $level, $key)) {
                if (is_array($object)) {
                    // build ancestor array list
                    $identifier = md5(rand());
                    $this->traverse($object, $hierarchy, $value, $index, $relationship, $level + 1, array_merge($ancestorArrays, [$identifier => $object]));
                } else {
                    if (($relationship === 'field' ? $key : $object) === $value) {
                        // todo: allow for negative indexes
                        $i = 0;
                        foreach ($ancestorArrays as $array) {
                            $i++;
                            // todo: obviously this matches the first only; have a setting that handles multiple
                            if ($i === $index) $this->__result = $array;
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @source https://stackoverflow.com/questions/4790453/php-recursive-array-to-object#4790485
     * @param $array
     * @return \stdClass
     */
    public function arrayToObjectDeep($array) {
        if (gettype($array) === 'object') return $array;

        $obj = new \stdClass;
        if (empty($array)) return $obj;

        foreach($array as $k => $v) {
            if(strlen($k)) {
                if(is_array($v)) {
                    $obj->{$k} = $this->arrayToObjectDeep($v);
                } else {
                    $obj->{$k} = $v;
                }
            }
        }
        return $obj;
    }

    /**
     * @param $hierarchy
     * @param $level
     * @param $key
     * @return bool
     */
    public function negotiate($hierarchy, $level, $key) {
        $meet = false;
        if (empty($hierarchy[$level])) {
            $meet = false;
        } elseif ($hierarchy[$level] instanceof IntKey && is_int($key)) {
            $meet = true;
        } elseif ($hierarchy[$level] instanceof StringKey && is_string($key)) {
            $meet = true;
        } else if ($hierarchy[$level] === $key) {
            $meet = true;
        }
        return $meet;
    }
}

