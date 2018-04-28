<?php

/**
 * @author Michael Rynn
 */
namespace Mod\Toml;

/** to pass around for recursive production */
class Writer {

    private $output;   // append output here
    private $isAOT; 
    private $tableName; // a bug or feature?
    /**
     * return Join of tags with '.' separator
     * @return string
     */
    
    public function __construct() {
        $this->init();
        $this->isAOT = false;
        $this->tableName = '';
        
    }
    private function init() {
        $this->output = '## TOML 0.4';
    }
    public function flushToFile($fileName) {
        $result = file_put_contents($fileName, $this->output);
        if ($result) {
            $this->init();
        }
        return $result;
    }

    /**
     * Get the current table name
     * @return string or null
     */
    public function getName() {
        return $this->tableName;
    }

    /**
     * True if table name is for Array of Tables
     * @return boolean
     */
    public function isAOT() {
        return $this->isAOT;
    }
    /**
     * Add a sub-section to current table name
     * Ends any Array of Tables in progress
     * @param type $tag
     */
    public function putSubTable(string $tag) {
        if (!empty($this->tableName)) {
            $this->tableName .= '.' . $tag;
        }
        else {
            $this->tableName = $tag;
        }
        $this->isAOT = false;
        $this->output .= PHP_EOL . '[' . $this->tableName . ']';
    }

    /**
     * Do not set with brackets.
     * Does no output.
     * @param type $tag
     */
    public function setName(string $tag) {
        
        $this->tableName = $tag;
    }

    /** Maybe to push something else ? */
    public function popName() {
        // Has a '.' in the middle?
        if (empty($this->tableName))
        {
            return null;
        }
        $dpos = strrpos($this->tableName,'.');
        if ($dpos >= 1) {
            // remove end from the '.'
            $this->tableName = substr($this->tableName,0, $dpos);
        }
        return $this->tableName;
    }

    public function putComment($str) {
        $this->output .= PHP_EOL . '# ' . $str;
    }

    /**
     * Write something like [[a.b]]
     * @param string $name
     */
    public function putAOT(string $name) {
        $this->setName($name);
        $this->isAOT = true;
        $this->output .= PHP_EOL . '[[' . $name . ']]';
    }

    /**
     * Write something like [a.b.c]
     * @param string $name
     */
    public function putTable(string $name) {
        $this->tableName = $name;
        $this->isAOT = false;
        $this->output .= PHP_EOL . '[' . $name . ']';
    }

    
    // TOML arrays for single valued, integer index
    static public function has_string_keys(array $array) {
        return (count(array_filter(array_keys($array), 'is_string')) > 0) ? true : false;
    }

    /**
     * Inline tables use { and key = value, on a single line
     * @param mixed $value
     */
    private function putInlineTable($table) {
        $this->output .= '{';
        $isFirst = true;
        foreach ($table as $key => $value) {
            if ($isFirst) {
                $isFirst = false;
            } else {
                $this->output .= ', ';
            }
            $this->putKeyValue($key, $value, '');
        }
        $this->output .= '}';
    }

    /**
     * Write array inline
     * @param array $arValue
     */
    private function putInlineArray($arValue) {
        if (self::has_string_keys($arValue)) {
            $this->putInlineTable($arValue);
            return;
        }
        $this->output .= '[';
        $isFirst = true;
        foreach ($arValue as $item) {
            if ($isFirst) {
                $isFirst = false;
            } else {
                $this->output .= ',';
            }
            $this->putValue($item);
        }
        $this->output .= ']';
    }

    /**
     * Output integer, string, boolean or inline array.
     * No objects please.
     * @param mixed $value
     */
    private function putValue($value) {
        $ptype = gettype($value);
        switch ($ptype) {
            case 'integer':
            case 'double' :
                $this->output .= $value;
                break;
            case 'boolean':
                $value = ($value) ? 'true' : 'false';
                $this->output .= $value;
                break;
            case 'string':
                $this->output .= '"' . addslashes($value) . '"';
                break;
            case 'array':
                $this->putInlineArray($value);
                break;
            case 'NULL':
                $this->output .= "''";
                break;
            default:
                throw new \Exception("Unhandled type '$ptype' for TOML value");
        }
    }

    /**
     * Write a single key = value, optional 
     * @param string $key
     * @param mixed $value
     * @param string $sep
     */
    public function putKeyValue($key, $value, $sep = PHP_EOL) {
        
        if (!preg_match('/^[A-Za-z0-9_-]+$/',$key)) {
            $key = "\"" . addslashes($key) . "\"";
        }
        $this->output .= $sep . $key . ' = ';
        $this->putValue($value);
    }

    /**
     * Write each key, value pair on a new line
     * @param array $map
     */
    public function putArray($map) {
        foreach ($map as $key => $value) {
            $this->putKeyValue($key, $value);
        }
    }

}
