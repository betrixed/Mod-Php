<?php

/**
 * @author Michael Rynn
 */

namespace Mod\Schema;

use Mod\Toml\Writer as TOMLWriter;
use Phalcon\Db\Reference;

/**
 * Intermediate type to and from on-file TOML table definition. 
 * Match read and write key names and format.
 */
class ReferenceDef extends NameDef {

    /**
     * Create a Phalcon\Db\Reference
     * @return Reference
     */
    public function makeReferenceClass() {
        return new Reference($this->name, $this->data);
    }

    public function getData() {
        return $this->data;
    }

    public function init($name, $data) {
        $this->name = $name;
        $this->data = $data;
    }

    static function isEqual(NameDef $a, NameDef $b) {
        return NameDef::isEqual($a, $b);
    }

    /**
     * Store specifics from Phalcon\Db\Reference
     * 
     * @param Reference $ref
     */
    public function setFromReference(Reference $ref) {
        $this->name = $ref->getName();
        $columns = $ref->getColumns();
        $referencedColumns = $ref->getReferencedColumns();

        $this->data = [];
        $def = &$this->data;

        $def['referencedTable'] = $ref->getReferencedTable();
        $def['referencedSchema'] = $ref->getReferencedSchema();
        $def['columns'] = array_unique($columns);
        $def['referencedColumns'] = array_unique($referencedColumns);
        $def['onUpdate'] = $ref->getOnUpdate();
        $def['onDelete'] = $ref->getOnDelete();
    }

    /**
     * Output as TOML format
     * @param TOMLWriter $toml
     */
    public function writeTOML(TOMLWriter $toml) {
        $toml->putKeyValue('name', $this->name);
        $toml->putSubTable('data');
        $toml->putArray($this->data);
    }

}
