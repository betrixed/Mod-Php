<?php
/**
 * @author Michael Rynn
 */
namespace Mod\Schema;

use Mod\Toml\Writer as TOMLWriter;
use Phalcon\Db\Index;
/** 
 * Intermediate type to and from on-file TOML table definition. 
 * Match read and write key names and format.
 */
class IndexDef extends NameDef {
    public $indexType;

    public function initIndex(string $name, $columns, $indexType = null) {
        parent::init($name, $columns);
        $this->indexType = $indexType;
    }
    
    public function setFromIndex(Index $ix)
    {
        $this->name = $ix->getName();
        $this->indexType = $ix->getType();
        $this->data = $ix->getColumns();
    }
    /**
     * May be 'UNIQUE or PRIMARY or null.
     * @return string 
     */
    public function getIndexType() {
        return $this->indexType;
    }
    /**
     * List of column names
     * @return array
     */
    public function getIndexColumns() {
        return $this->data;
    }
    /**
     * Does argument IndexDef match this in everything?
     * @param IndexDef $a
     * @param IndexDef $b
     * @return boolean
     */
    static function isEqual(NameDef $a, NameDef $b) {
        return ($a->indexType == $b->indexType) && (NameDef::isEqual($a, $b));
    }
    /** 
     * Construct and return Phalcon\Db\Index from this object.
     * @return Index 
     */
    public function makeIndexClass()
    {
        return new Index($this->name, $this->data, $this->indexType);
    }
    /** 
     * Output to TOML format string.
     * @param TOMLWriter $toml
     */
    public function writeTOML(TOMLWriter $toml) {
        $toml->putKeyValue('name', $this->name);
        if (!empty($this->indexType))
        {
            $toml->putKeyValue('type', $this->indexType);
        }
        $toml->putKeyValue('columns', $this->data);
    }
}
