<?php

namespace Mod\Toml;

use Pun\KeyTable;
use Pun\ValueList;

class DStack
{
    public $ref;
    public $dataType;
    public $isTable;
}

class XmlToml extends \XMLReader
{

    public $root;
    public $table;
    public $path;
    public $isTable; // true for table, false for value list array
    public $dataType;
    public function &parseFile($path)
    {

        $this->open($path);
        try {
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
        } finally {
            $this->close();
        }
        return $this->root;
    }

    public function popTable()
    {
        $ct = count($this->path);
        if ($ct > 0) {
            array_pop($this->path);
            $ct--;
            if ($ct > 0) {
                $dstack = $this->path[$ct-1];
                $this->table = $dstack->ref;
                $this->isTable = $dstack->isTable;
                $this->dataType = $dstack->dataType;
            }
        } else {
           throw new \Exception("Pop on empty table stack");
        }
    }

    public function pushTable($atype = null, $k = null)
    {
        // PHP arrays will require & for assignment
        //$ntb = []; 
        $wasTable = $this->isTable;
        if (!empty($atype)) {
            $ntb = new ValueList;
            $this->isTable = false;
        } else {
            $ntb = new KeyTable;
            $this->isTable = true;
        }
        if (empty($this->root)) {
            $this->root = $ntb;
            $this->isTable = true;
        } else {
            if (!empty($k)) {
                // use key
                if (!$wasTable) {
                    throw new \Exception("Parent is not table");
                }
                $this->table[$k] = $ntb;
            }
            else {
                // push end array
                if ($wasTable) {
                    throw new \Exception("Parent is not list");
                }
                $this->table->pushBack($ntb);
            }
        }
        $this->table = $ntb;
        $this->dataType = $atype;
        $dstack = new DStack();
        $dstack->ref = $ntb;
        $dstack->dataType = $atype;
        $dstack->isTable = $this->isTable;
        $this->path[] = $dstack;
    }

    public function element()
    {
        $k = $this->getAttribute('k');
        if (is_null($k)) {
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
            case "s" :
                $stemp = $this->readString();
                if ($this->isTable) {
                    $this->table[$k] = $stemp;
                }
                else {
                    $this->table->pushBack($stemp);
                }
                break;
            case "b" :
                $s = $this->readString();
                $btemp = ($s === "true") ? true : false;
                if ($this->isTable) {
                    $this->table[$k] = $btemp;
                }
                else {
                    $this->table->pushBack($btemp);
                }                
                break;
        }
    }

}
