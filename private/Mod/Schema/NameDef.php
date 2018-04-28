<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mod\Schema;

/**
 * This seems too simple to bother, but it seems like a useful place for
 * a tiny piece of common functionality
 *
 * @author Michael Rynn
 */
class NameDef {

    //put your code here
    protected $name;
    protected $data;
    
    public function __construct() {
        $this->data = [];
    }
    
    public function init($name, $data) {
        $this->name = $name;
        if (!is_array($data)) {
            $data = $data->toArray();
        }
        $this->data = $data;
    }
    
    /**
     * True if all particulars of data match.
     * @param NameDef $a
     * @param NameDef $b
     * @return boolean
     */
    
    static function isEqual(NameDef $a, NameDef $b) {
        return ($a->name == $b->name) 
            && (NameDef::arraytree_equal($a->data, $b->data));
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function setName($val) {
        $this->name = $val;
    }

    public function unsetValue($key) {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }
    public function getValue($key) {
        return (isset($this->data[$key]))
                ? $this->data[$key]
                : null;
    }
    /**
     * Adjust or add a column property
     * @param type $key
     * @param type $value
     */
    public function setValue($key, $value) {
        $this->data[$key] = $value;
    }
/**
 * Examine array references and return difference as array.
 * Does not modify referenced arrays.
 * @param array $a
 * @param array $b
 * @return array
 */
    static public function array_recurse_diff(array &$a, array &$b) {
        $aReturn = [];
        foreach ($a as $aKey => $aValue) {
            if (isset($b[$aKey])) { // array_key_exists better?
                if (is_array($aValue)) {
                    $aRecDiff = array_recurse_diff($aValue, $b[$aKey]);
                    if (count($aRecDiff)) {
                        $aReturn[$aKey] = $aRecDiff;
                    }
                } else {
                    if ($aValue != $b[$aKey]) {
                        $aReturn[$aKey] = $aValue;
                    }
                }
            }
            else {
                $aReturn[$aKey] = $aValue;
            }
        }
        return $aReturn;
    }

    /**
     * 
     * @param array $a
     * @param array $b
     * @return boolean if a and b match all the way
     */
    static public function arraytree_equal(array &$a, array &$b) {
        if (!is_array($a) || !is_array($b) || (count($a) != count($b))) {
           return false;
        }
        return (count(self::array_recurse_diff($a, $b)) == 0) && (count(self::array_recurse_diff($b, $a)) == 0) ? true : false;   
    }

}
