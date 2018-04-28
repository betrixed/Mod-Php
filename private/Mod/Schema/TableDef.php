<?php

/**
 * @author Michael Rynn
 */

namespace Mod\Schema;

//use Phalcon\Db\Column;
//use Phalcon\Db\Reference;
use Pun\TomlReader as TOMLParser;
use Mod\Toml\Writer as TOMLWriter;
use Phalcon\Db\Column as DbColumn;
use Phalcon\Db\Index as DbIndex;
use Phalcon\Db\Reference as DbReference;

/**
 * Intermediate class for SQL table definition. 
 * Save and Load from TOML file format.
 * Generate more or less directly the 
 * table definitions used by Phalcon SQL database classes.
 */
class TableDef {

    public $name;
    public $schema;
    public $importRows;
    public $columns;
    public $indexes;
    public $references;
    public $version;
    public $options;
    public $noSchema; // ignore schema name

    public function __construct() {
        $this->columns = [];
        $this->indexes = [];
        $this->references = [];
        $this->noSchema = true;
    }

    /** Get data definitions of References 
     * 
     * @return array of ReferenceDef
     */
    public function getReferenceDefs() {
        return $this->references;
    }

    /** Get data definitions of Indexes 
     * 
     * @return array of IndexDef
     */
    public function getIndexDefs() {
        return $this->indexes;
    }

    /** Get data definitions of Columns 
     * 
     * @return array of ColumnDef
     */
    public function getColumnDefs() {
        return $this->columns;
    }

    /**
     * Save this instance as TOML format file.
     * @param string $fileName
     * @return integer number of bytes written, or boolean false
     */
    public function saveTOML(string $fileName) {
        $toml = new TOMLWriter();
        $this->writeTOML($toml);
        return $toml->flushToFile($fileName);
    }

    public function getName() {
        return $this->name;
    }

    public function setVersion(string $vstr) {
        $this->version = $vstr;
    }

    public function getVersion() {
        return $this->version;
    }

    public function getSchema(){
        return $this->schema;
    }
    
    public function setImportRows(int $rows) {
        $this->importRows = $rows;
    }
    
    public function getImportRows() {
        return $this->importRows;
    }
    public function setSchema(string $val) {
        $this->schema = $val;
    }
    /**
     * Return list of columns that has parameter $key with value $value
     * @param type $key
     * @param type $value
     */
    public function getColumnsByProperty($key, $value) {
        $list = [];
        foreach($this->columns as $cdef) {
            $test = $cdef->getValue($key);
            if ($test==$value){
               $list[] = $cdef;
            }
        }
        return $list;
    }
    
    /** Get list of column names in order of columns
     * 
     * @return array of string
     */
    public function getFieldNames() {
        return array_map(function($val) { return $val->getName();}, $this->columns);
    }
    /**
     * Get the integer offsets for each column name, indexed by name
     * 
     * @return array of name => integer offsets from 0
     */
    public function getColumnOffsets()
    {
        $lookup = [];
        foreach($this->columns as $idx => $col) {
            $lookup[$col->getName()] = $idx;
        }
        return $lookup;
    }
    public function getIndexNames() {
        $list = [];
        foreach($this->indexes as $idx) {
            $list[$idx->getName()] = $idx;
        }
        return $list;
    }
    public function getIndexesByType($type) {
        $list = [];
        foreach($this->indexes as $idx) {
            if ($idx->getIndexType() == $type) {
                $list[] = $idx;
            }
        }
        return $list;
    }
    
    public function getNonPrimaryIndexes() {
        $list = [];
        foreach($this->indexes as $idx) {
            if ($idx->getIndexType() != 'PRIMARY') {
                $list[] = $idx;
            }
        }
        return $list;        
    }
    private function readVersion01($table, $def) {
        $this->version = $table['version'];
        $this->importRows = $table['importRows'];
        $this->schema = $table['schema'];
        $this->name = $table['name'];

        $colCt = $table['columns'];
        if ($colCt > 0) {
            $ci = $def['column'];
            for ($i = 1; $i <= $colCt; $i += 1) {
                $col = $ci[$i];
                $cdef = new ColumnDef();
                $cdef->init($col['name'], $col['data']);
                $this->columns[] = $cdef;
            }
        }


        $indexCt = $table['indexes'];
        if ($indexCt > 0) {
            $ii = $def['index'];
            for ($i = 1; $i <= $indexCt; $i += 1) {
                $idx = $ii[$i];
                $idxType = (isset($idx['type'])) ? $idx['type'] : '';
                $idef = new IndexDef();
                $cdata =  $idx['columns'];
                if (!is_array($cdata)) {
                    $cdata = $cdata->toArray();
                }
                $idef->initIndex($idx['name'], $cdata, $idxType);
                $this->indexes[] = $idef;
            }
        }

        $refCt = $table['references'];
        if ($refCt > 0) {
            $ri = $def['reference'];
            for ($i = 1; $i <= $refCt; $i += 1) {
                $ref = $ri[$i];
                $rdef = new ReferenceDef();
                $rdef->init($ref['name'], $ref['data']);
                $this->references[] = $rdef;
            }
        }
        if (isset($def['options'])) {
            $odata = $def['options'];         
            if (!is_array($odata)) {
                $odata = $odata->toArray();
            }
            $this->options = $odata;
        }
    }

    /**
     * Reconstruct this object from a TOML file.
     * Should only be done on a new instance.
     * @param string $fileName
     * @throws \Exception
     */
    public function readTOML(string $fileName) {
        $def = TOMLParser::parseFile($fileName);
        // get table 'table' section
        $table = $def['table'];
        $defVersion = $table['tdefVersion'];
        if ($defVersion == '0.1') {
            $this->readVersion01($table, $def);
        } else {
            throw new \Exception('Expected version 0.1, got ', $defVersion);
        }
    }

/**
 * Add a column defintion from a Column object
 * @param DbColumn $col
 * @param string $dbAdapter
 * @return \Schema\ColumnDef
 */
    public function addColumnDef(DbColumn $col, string $dbAdapter) {
        $cdef = new \Schema\ColumnDef();
        $cdef->setFromColumn($col, $dbAdapter);
        $this->columns[] = $cdef;
        return $cdef;
    }

    /**
     * @return Array of Phalcon\Db\Column objects
     */
    public function getDbColumns() {
        $columns = [];
        foreach ($this->columns as $cdef) {
            $columns[] = $cdef->makeColumnClass();
        }
        return $columns;
    }

    /**
     * 
     * return list of column names and matching type 
     * @param array $dataTypes
     */
    public function getFieldDataTypes() {
        $result = [];
        foreach ($this->columns as $coldef) {
            $result[$coldef->getName()] = $coldef->getValue('type');
        }
        return $result;
    }

    /**
     * @return array of Phalcon\Db\Index objects
     */
    public function getDbIndexes() {
        $indexes = [];
        foreach ($this->indexes as $idef) {
            $indexes[] = $idef->makeIndexClass();
        }
        return $indexes;
    }


    /**
     * @return array of Phalcon\Db\Reference objects
     */
    public function getDbReferences() {
        $references = [];
        foreach ($this->references as $ref) {
            $references[] = $ref->makeIndexClass();
        }
        return $references;
    }

    /**
     * Return key-value pairs for table options
     * @return array
     */
    public function getDbOptions() {
        return $this->options;
    }

    /** @return array with 
     * all definition parts - columns, indexes, references, options
     */
    public function getTableDefinition() {
        return [
            'columns' => $this->getDbColumns(),
            'indexes' => $this->getDbIndexes(),
            'options' => $this->getDbOptions(),
            'references' => $this->getDbReferences()
        ];
    }

    /**
     * Create and append new IndexDef
     * @param Index $idx
     * @return IndexDef
     */
    public function addIndexDef(DbIndex $idx) {
        $idef = new IndexDef();
        $idef->setFromIndex($idx);
        $this->indexes[] = $idef;
        return $idef;
    }

    /**
     * Add a new ReferenceDef from Db\Reference
     * @param \Toml\Reference $ref
     * @return ReferenceDef
     */
    public function addReferenceDef(DbReference $ref) {
        $rdef = new ReferenceDef();
        $rdef->setFromReference($ref);
        $this->references[] = $rdef;
        return $rdef;
    }

    /**
     * Set a table 'option'
     * @param string $optionName
     * @param type $optionValue
     */
    public function setOption(string $optionName, $optionValue) {
        $this->options[$optionName] = $optionValue;
    }

    public function getColumnCount() {
        return count($this->columns);
    }

    public function getIndexCount() {
        return count($this->indexes);
    }

    public function getReferenceCount() {
        return count($this->references);
    }

    /**
     * Dump this object to a TOML file 
     * @param TOMLWriter $toml
     */
    public function writeTOML(TOMLWriter $toml) {
        $toml->putTable('table');
        $toml->putKeyValue('tdefVersion', '0.1');

        $toml->putKeyValue('name', $this->name);
        $toml->putKeyValue('schema', $this->schema);
        $toml->putKeyValue('importRows', $this->importRows);

        $toml->putKeyValue('columns', count($this->columns));
        $colCt = (!empty($this->indexes)) ? count($this->indexes) : 0;
        $toml->putKeyValue('indexes', $colCt);
        $refCt = (!empty($this->references)) ? count($this->references) : 0;
        $toml->putKeyValue('references', $refCt);
        $toml->putKeyValue('version', $this->version);

        foreach ($this->columns as $offset => $col) {
            $toml->putTable('column.' . ($offset + 1));
            $col->writeTOML($toml);
        }

        foreach ($this->indexes as $offset => $idx) {
            $toml->putTable('index.' . ($offset + 1));
            $idx->writeTOML($toml);
        }

        foreach ($this->references as $offset => $ref) {
            $toml->putTable('reference.' . ($offset + 1));
            $ref->writeTOML($toml);
        }

        $toml->putTable('options');
        $toml->putArray($this->options);
    }


}
