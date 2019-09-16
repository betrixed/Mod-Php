<?php
namespace Mod;

class DStack {
    public $ref;
    public $dataType;
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

    public $addRoot;
    public $root;
    public $table; // reference to array
    public $config; // config object
    public $path;
    public $dataType;

    public function __construct(XmlConfig $add = null) {
        $this->addRoot = $add;
        $this->dataType = null;
    }
    /**
     * Return array by reference
     * @param type $path
     * @return type
     * @throws type
     * @return array
     */
    public function parseFile($path) {
        $old_error_handler = set_error_handler("\WC\intercept_error");
        $toThrow = null;
        try {
            $this->open($path);
            while ($this->read()) {
                if ($this->nodeType === \XMLReader::ELEMENT) {
                    $this->element();
                } else if ($this->nodeType === \XMLReader::TEXT) {
                    
                } else if ($this->nodeType === \XMLReader::END_ELEMENT) {
                    if ($this->name === "tb" || $this->name === "a" || $this->name === "root") {
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
                $ptype = $dstack->dataType;
                switch($ptype) {
                    CASE XmlConfig::XC_TABLE:
                    CASE XmlConfig::XC_ARRAY:
                        $this->table = &$dstack->ref;
                        break;
                    CASE XmlConfig::XC_CONFIG: 
                        $this->config = $dstack->ref;
                        break;
                    
                }
                $this->dataType = $ptype;
            }
        } else {
            throw new \Exception("Pop on empty table stack");
        }
    }

    public function pushRoot($k = null) {
        $ptype = $this->dataType;
        if (is_null($ptype)) {
            if ($this->addRoot) {
                $nroot = $this->addRoot;
            }
            else {
                $nroot = new XmlConfig();
            }
            $this->root = $nroot;
        } else {
            $nroot = new XmlConfig();
            // check out type of current "table"
            if (!empty($k)) {
                // use key
                switch($ptype) {
                    CASE XmlConfig::XC_CONFIG:
                        $this->config->$k = $nroot;
                        break;
                    CASE XmlConfig::XC_TABLE:
                        $this->table[$k] = $nroot;
                        break;
                    DEFAULT:
                        throw new \Exception("Parent not indexed");
                        break;
                }   
            } else {
                // push end array
                if ($ptype !== XmlConfig::XC_ARRAY) {
                    throw new \Exception("Parent not a list");
                }
                //$this->table->pushBack($ntb);
                
                $this->table[] = $nroot;
            }
        }
        //$this->table = null;
        //If ->table is a reference, assigning to it does weird sxxx
        $this->config = $nroot;
        $this->dataType = XmlConfig::XC_CONFIG;
        $dstack = new DStack();
        $dstack->ref = $nroot;
        $dstack->dataType = XmlConfig::XC_CONFIG;
        $this->path[] = $dstack;
    }
    public function pushTable($atype = null, $k = null) {
        // PHP arrays will require & for assignment
        $ntb = [];
        $ptype = $this->dataType;
        if (is_null($this->root)) {
            $this->root = &$ntb;
            $this->isTable = true;
        } else {
            if (!empty($k)) {
                switch($ptype) {
                    CASE XmlConfig::XC_CONFIG:
                        $this->config->$k =  &$ntb;
                        break;
                    CASE XmlConfig::XC_TABLE:
                        $this->table[$k] = &$ntb;
                        break;
                    DEFAULT:
                        throw new \Exception("Parent is not indexed");
                        break;
                }
            } else {
                // push end array
                if (XmlConfig::XC_ARRAY !== $ptype) {
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
        $this->path[] = $dstack;
    }

    public function setValue($val, $k = null) {
        switch($this->dataType) {
            CASE XmlConfig::XC_CONFIG:
                $this->config->$k =  $val;
                break;
            CASE XmlConfig::XC_TABLE:
                $this->table[$k] = $val;
                break;
            CASE XmlConfig::XC_ARRAY:
                $this->table[] = $val;
                break;
            DEFAULT:
                throw new \Exception("Parent is not indexed");
                break;
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
            case "root":
                $this->pushRoot($k);
                break;
            case "tb" :
                $this->pushTable(XmlConfig::XC_TABLE, $k);
                break;
            case "a" :
                // start array, which means no key for elements
                $this->pushTable(XmlConfig::XC_ARRAY, $k);
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
