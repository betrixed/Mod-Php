<?php
namespace Mod;

class DStack {

    public $ref;
    public $dataType;
    public $isTable;

}

function intercept_error($errno, $errstr, $errfile, $errline) {
    throw new \Exception($errstr . "  at line " . $errline . " in " . $errfile);
}
/** Read specific xml file format into a nested php array 
 *   Elements are 
 * tb - associative PHP array
 * a - sequential PHP array
 * s - string
 * i - integer
 * d - date
 * t - datetime
 * b - boolean
 * 
 * Attributes are
 * k - string key for associative array  
 */
class XmlArray extends \XMLReader {

    public $root;
    public $table;
    public $path;
    public $isTable; // true for table, false for value list array
    public $dataType;

    /**
     * Return array by reference
     * @param type $path
     * @return type
     * @throws type
     * @return array
     */
    public function &parseFile($path) : array {
        $old_error_handler = set_error_handler("\WC\intercept_error");
        $toThrow = null;
        try {
            $this->open($path);
            while ($this->read()) {
                if ($this->nodeType === \XMLReader::ELEMENT) {
                    $this->element();
                } else if ($this->nodeType === \XMLReader::TEXT) {
                    
                } else if ($this->nodeType === \XMLReader::END_ELEMENT) {
                    if ($this->name === "tb" || $this->name === "a") {
                        $this->popTable();
                    }
                }
            }
        } catch (\Throwable $e) {
            $toThrow = new \Exception($e->getMessage() . PHP_EOL . "Failed to read " . $path);
        } finally {
            $this->close();
            set_error_handler($old_error_handler);
        }
        if ($toThrow) {
            throw $toThrow;
        }
        return $this->root;
    }

    public function popTable() {
        $ct = count($this->path);
        if ($ct > 0) {
            array_pop($this->path);
            $ct--;
            if ($ct > 0) {
                $dstack = $this->path[$ct - 1];
                $this->table = &$dstack->ref;
                $this->isTable = $dstack->isTable;
                $this->dataType = $dstack->dataType;
            }
        } else {
            throw new \Exception("Pop on empty table stack");
        }
    }

    public function pushTable($atype = null, $k = null) {
        // PHP arrays will require & for assignment
        $ntb = [];
        $wasTable = $this->isTable;
        if (!empty($atype)) {
            //$ntb = new ValueList;
            $this->isTable = false;
        } else {
            //$ntb = new KeyTable;
            $this->isTable = true;
        }
        if (is_null($this->root)) {
            $this->root = &$ntb;
            $this->isTable = true;
        } else {
            if (!empty($k)) {
                // use key
                if (!$wasTable) {
                    throw new \Exception("Parent is not table");
                }
                $this->table[$k] = &$ntb;
            } else {
                // push end array
                if ($wasTable) {
                    throw new \Exception("Parent is not list");
                }
                //$this->table->pushBack($ntb);
                $this->table[] = &$ntb;
            }
        }
        //$this->table = $ntb;
        $this->table = &$ntb;
        $this->dataType = $atype;
        $dstack = new DStack();
        $dstack->ref = &$ntb;
        $dstack->dataType = $atype;
        $dstack->isTable = $this->isTable;
        $this->path[] = $dstack;
    }

    public function setValue($val, $k = null) {
        if ($this->isTable) {
            $this->table[$k] = $val;
        } else {
            //$this->table->pushBack($stemp);
            $this->table[] = $val;
        }
    }

    public function element() {
        $k = $this->getAttribute('k');
        if (is_null($k) && !empty($this->path)) {
            $ct = count($this->path);
            if ($ct > 0) {
                $dstack = $this->path[$ct - 1];
                if ($dstack->dataType === null) {
                    throw new \Exception("Missing key (k) attribute for " . $this->name);
                }
            }
        }
        switch ($this->name) {
            case "tb" :
                $this->pushTable(null, $k);
                break;
            case "a" :
                // start array, which means no key for elements
                $this->pushTable("a", $k);
                break;
            case "i" :
                $this->setValue(intval($this->readString()), $k);
                break;
            case "_n":
                $this->setValue(null, $k);
                break;
            case "s" :
                $this->setValue($this->readString(), $k);
                break;
            case "b" :
                $s = $this->readString();
                $btemp = ($s === "true") ? true : false;
                $this->setValue($btemp, $k);
                break;
            case "f":
                $this->setValue(floatval($this->readString()), $k);
                break;
        }
    }

    static function isAssoc(&$arr) {
        if ([] === $arr)
            return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    /**
     * 
     * @param type $ref - reference to array
     * @param type $outs - reference to string
     * @param type $doKey
     * @param string $indent
     */
    static function arrayToXml(&$ref, &$outs, $doKey = true, $indent = "") {
        $indent .= "  ";
        foreach ($ref as $k => $v) {
            $keyPart = $doKey ? " k=\"$k\"" : "";
            if (is_array($v)) {
                $hasKey = static::isAssoc($v);
                $tb = $hasKey ? "tb" : "a";
                $outs .= $indent . "<$tb$keyPart>" . PHP_EOL;
                static::arrayToXml($v, $outs, $hasKey, $indent);
                $outs .= $indent . "</$tb>" . PHP_EOL;
            } else if (is_integer($v)) {
                $outs .= $indent . "<i$keyPart>" . $v . "</i>" . PHP_EOL;
            } else if (is_null($v)) {
                $outs .= $indent . "<_n$keyPart></_n>" . PHP_EOL;
            } else if (is_bool($v)) {
                $outs .= $indent . "<b$keyPart>" . $v . "</b>" . PHP_EOL;
            }  else if (is_numeric($v)) {
                $outs .= $indent . "<f$keyPart>" . $v . "</f>" . PHP_EOL;
            } 
            else {
                $outs .= $indent . "<s$keyPart>" . $v . "</s>" . PHP_EOL;
            }
        }
    }

    static function toXmlDoc(&$ref, $key) {
        $outs = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>" . PHP_EOL;
        $outs .= "<tb>" . PHP_EOL;

        $outs .= "<tb k=\"$key\" >" . PHP_EOL;

        static::arrayToXml($ref, $outs);

        $outs .= "</tb>" . PHP_EOL;
        $outs .= "</tb>" . PHP_EOL;
        return $outs;
    }

}
