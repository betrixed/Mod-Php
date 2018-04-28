<?php

/**
 * @author Michael Rynn
 */

namespace Mod\Schema;

use Mod\Toml\Writer as TOMLWriter;
use Phalcon\Db\Column;
use Phalcon\Exception\Db\UnknownColumnTypeException;

/**
 * Intermediate type to and from on-file TOML table definition. 
 * Match read and write key names and format.
 * Conversion between Column type constants and column type names.
 */
class ColumnDef extends NameDef {

    public function getData() {
        return $this->data;
    }


    public function writeTOML(TOMLWriter $toml) {
        $toml->putKeyValue('name', $this->name);
        $toml->putKeyValue('data', $this->data);
    }

    /**
     * Set name and data from a Column object
     * Will wipe out any prior settings
     * @param Column $field
     * @param string $dbAdapter
     * 
     */
    public function setFromColumn(Column $field, $dbAdapter) {
        $this->name = $field->getName();
        $def = [];
        $typeId = $field->getType();
        $typeName = self::TypeNameFromId($typeId);
        $numberFields = []; // don't need quotes?

        if (empty($typeName)) {
            throw new UnknownColumnTypeException($field);
        }
        $def['type'] = $typeName;
        if ($dbAdapter == 'Postgresql' &&
                in_array($typeId, [Column::TYPE_BOOLEAN, Column::TYPE_INTEGER, Column::TYPE_BIGINTEGER])
        ) {
            // No size
        } else {
            $def['size'] = ($fsize = $field->getSize()) ? $fsize : 1;
        }
        if ($field->hasDefault() && !$field->isAutoIncrement()) {
            $def['default'] = $field->getDefault();
        }
        // For singular primary key column? 
        // If no index defined?
        if ($field->isPrimary()) {
            $def['primary'] = true;
        }

        if ($field->isUnsigned()) {
            $def['unsigned'] = true;
        }

        if ($field->isNotNull()) {
            $def['notNull'] = true;
        }

        if ($field->isAutoIncrement()) {
            $def['autoIncrement'] = true;
        }

        if (($fscale = $field->getScale())) {
            $def['scale'] = $fscale;
        }
        $this->data = $def;
    }
    
    /** Keys of differences to ignore in isEqual */
    static public $notImportant = ['first', 'after'];
    
    /** Unset $notImportant keys in difference result */
    static function deleteNotImportant(array &$a) {
        foreach(self::$notImportant as $unkey) {
            if (isset($a[$unkey])) {
                unset($a[$unkey]);
            }
        }
    }
    static function isEqual(NameDef $a, NameDef $b) {
        
        $aDiff = NameDef::array_recurse_diff($a->data, $b->data);
        $bDiff = NameDef::array_recurse_diff($b->data, $a->data);
        
        if (!empty($aDiff)) {
            self::deleteNotImportant($aDiff);
        }
        if (!empty($bDiff)) {
            self::deleteNotImportant($aDiff);
        }
        
        return (empty($aDiff) && empty($bDiff)) ? true : false;
    }

    /**
     * Construct and return a Phalcon\Db\Column object
     * @return Column
     */
    public function makeColumnClass() {
        $coldata = $this->data;
        // modify the 'type'
        $typeid = $coldata['type'];
        $ptype = gettype($typeid);
        if ($ptype == 'string') {
            $coldata['type'] = self::ColumnIdFromType($typeid);
        }
        return new Column($this->name, $coldata);
    }

    private static $TypeLookup = [
        'INTEGER' => Column::TYPE_INTEGER,
        'VARCHAR' => Column::TYPE_VARCHAR,
        'CHAR' => Column::TYPE_CHAR,
        'DATE' => Column::TYPE_DATE,
        'DATETIME' => Column::TYPE_DATETIME,
        'TIMESTAMP' => Column::TYPE_TIMESTAMP,
        'DECIMAL' => Column::TYPE_DECIMAL,
        'TEXT' => Column::TYPE_TEXT,
        'BOOLEAN' => Column::TYPE_BOOLEAN,
        'FLOAT' => Column::TYPE_FLOAT,
        'DOUBLE' => Column::TYPE_DOUBLE,
        'TINYBLOB' => Column::TYPE_TINYBLOB,
        'MEDIUMBLOB' => Column::TYPE_MEDIUMBLOB,
        'LONGBLOB' => Column::TYPE_LONGBLOB,
        'JSON' => Column::TYPE_JSON,
        'JSONB' => Column::TYPE_JSONB,
        'MEDIUMBLOB' => Column::TYPE_MEDIUMBLOB,
        'BIGINTEGER' => Column::TYPE_BIGINTEGER,
    ];

    /**
     * Return matching Db\Column integer constant for SQL type as string.
     * @param string $colType
     * @return integer or null if no match.
     */
    public static function ColumnIdFromType(string $colType) {
        return isset(self::$TypeLookup[$colType]) ?
                self::$TypeLookup[$colType] : null;
    }

    /**
     * Convert from Column::TYPE_SQLTYPE value to a 'SQLTYPE' string
     * @param integer $colTypeId
     * @return string
     */
    public static function TypeNameFromId(int $colTypeId) {
        switch ($colTypeId) {
            case Column::TYPE_INTEGER:
                return 'INTEGER';
            case Column::TYPE_VARCHAR:
                return 'VARCHAR';
            case Column::TYPE_CHAR:
                return 'CHAR';
            case Column::TYPE_DATE:
                return 'DATE';
            case Column::TYPE_DATETIME:
                return 'DATETIME';
            case Column::TYPE_TIMESTAMP:
                return 'TIMESTAMP';
            case Column::TYPE_DECIMAL:
                return 'DECIMAL';
            case Column::TYPE_TEXT:
                return 'TEXT';
            case Column::TYPE_BOOLEAN:
                return 'BOOLEAN';
            case Column::TYPE_FLOAT:
                return 'FLOAT';
            case Column::TYPE_DOUBLE:
                return 'DOUBLE';
            case Column::TYPE_TINYBLOB:
                return 'TINYBLOB';
            case Column::TYPE_BLOB:
                return 'BLOB';
            case Column::TYPE_MEDIUMBLOB:
                return 'MEDIUMBLOB';
            case Column::TYPE_LONGBLOB:
                return 'LONGBLOB';
            case Column::TYPE_JSON:
                return 'JSON';
            case Column::TYPE_JSONB:
                return 'JSONB';
            case Column::TYPE_BIGINTEGER:
                return 'BIGINTEGER';
            default:
                return null;
        }
    }
}
