<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Tools                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2017 Phalcon Team (https://www.phalconphp.com)      |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
 */

namespace Mod\Schema;

use Phalcon\Utils;
use DirectoryIterator;
use Phalcon\Version\ItemInterface;
use Phalcon\Db\Exception as DbException;
use Phalcon\Db\Adapter;
use Phalcon\Version\ItemCollection as VersionCollection;
use Phalcon\Utils\Nullify;
use Mod\Path;

/**
 * Many static functions used by Schema\SchemaConfig
 * Schema\Migration
 * 
 */
class Migrate {

    const DIRECTION_FORWARD = 1;
    const DIRECTION_BACK = -1;

    /**
     * Generates all the class migration definitions for certain database setup
     *
     * @param  ItemInterface $version
     * @param  string        $exportData
     *
     * @return array
     */
    public static function generateAll(ItemInterface $version, $exportData = null) {
        $classDefinition = [];
        $schema = Utils::resolveDbSchema(self::$_databaseConfig);

        foreach (self::$_connection->listTables($schema) as $table) {
            $classDefinition[$table] = self::generate($version, $table, $exportData);
        }

        return $classDefinition;
    }

    /**
     * Return a list of filenames without path or extension,
     * of files with extension .toml in directory $path.
     * @param string $path
     * @return array of filename
     */
    public static function getTableDefList(string $path) {
        // make build_# files from filenames in $path
        $list = [];
        $tdir = Path::endSep($path);
        $dir = scandir($tdir);
        foreach ($dir as $item) {
            $info = pathinfo($item);
            if (array_key_exists('extension', $info)) {
                if ($info['extension'] == 'toml') {
                    $tdef = new TableDef();
                    $tdef->readTOML($tdir . $info['filename'] . '.toml');
                    $list[$tdef->getName()] = $tdef;
                }
            }
        }
        return $list;
    }

    /**
     * Migrate
     * @param \Phalcon\Version\IncrementalItem|\Phalcon\Version\TimestampedItem $fromVersion
     * @param \Phalcon\Version\IncrementalItem|\Phalcon\Version\TimestampedItem $toVersion
     * @param string  $tableName
     */
    public static function migrate($fromVersion, $toVersion, $tableName) {
        if (!is_object($fromVersion)) {
            $fromVersion = VersionCollection::createItem($fromVersion);
        }

        if (!is_object($toVersion)) {
            $toVersion = VersionCollection::createItem($toVersion);
        }

        if ($fromVersion->getStamp() == $toVersion->getStamp()) {
            return; // nothing to do
        }

        if ($fromVersion->getStamp() < $toVersion->getStamp()) {
            $toMigration = self::createClass($toVersion, $tableName);
            if ($toMigration instanceof \Toml\TableDef) {

                if (method_exists($toMigration, 'morph')) {
                    $toMigration->morph();
                }

// modify the datasets
                if (method_exists($toMigration, 'up')) {
                    $toMigration->up();
                    if (method_exists($toMigration, 'afterUp')) {
                        $toMigration->afterUp();
                    }
                }
            }
        } else {
// rollback!
// reset the data modifications
            $fromMigration = self::createClass($fromVersion, $tableName);
            if (is_object($fromMigration) && method_exists($fromMigration, 'down')) {
                $fromMigration->down();

                if (method_exists($fromMigration, 'afterDown')) {
                    $fromMigration->afterDown();
                }
            }

// call the last morph function in the previous migration files
            $toMigration = self::createPrevClassWithMorphMethod($toVersion, $tableName);

            if (is_object($toMigration)) {
                if (method_exists($toMigration, 'morph')) {
                    $toMigration->morph();
                }
            }
        }
    }

    /**
     * Get array of objects as named array.
     * @param type $array of objects which have a getName,
     * to be used as index for associative array
     */
    static private function arrayByName(array $objects) {
        $byName = [];
        foreach ($objects as $namedObject) {
            $byName[$namedObject->getName()] = $namedObject;
        }
        return $byName;
    }

    /**
     * Drop references that are changed or missing for new table definition
     * @param Adapter $db
     * @param TableDef $tableDef
     */
    static public function dropOldReferences(Adapter $db, TableDef $tableDef) {
        $newReferenceDefs = self::arrayByName($tableDef->getReferenceDefs()); // name lookup
        $tableName = $tableDef->getName();
        $defaultSchema = $tableDef->getSchema();

        $mylist = $db->describeReferences($tableName, $defaultSchema);
        $myReferenceDefs = [];
        foreach ($mylist as $ref) {
            $def = new ReferenceDef();
            $def->setFromReference($ref);
            $myReferenceDefs[$def->getName()] = $def;
        }
        // Are matching named references exactly the same?
        foreach ($newReferenceDefs as $refName => $rdef) {
            if (isset($myReferenceDefs[$refName])) {
                if (!ReferenceDef::isEqual($rdef, $myReferenceDefs[$refName])) {
                    $db->dropForeignKey($tableName, $defaultSchema, $refName);
                }
            }
        }
        // What needs to be dropped?
        foreach ($myReferenceDefs as $refName => $rdef) {
            if (!isset($newReferences[$refName])) {
                $db->dropForeignKey($tableName, $defaultSchema, $refName);
            }
        }
    }

    /**
     * Add new references that are missing from current table
     * @param Adapter $db
     * @param TableDef $tableDef
     */
    static public function addNewReferences(Adapter $db, TableDef $tableDef) {
        $newReferenceDefs = self::arrayByName($tableDef->getReferenceDefs()); // name lookup
        $tableName = $tableDef->getName();
        $defaultSchema = $tableDef->getSchema();

        $mylist = $db->describeReferences($tableName, $defaultSchema);
        $myReferenceNames = []; // only need the names 
        foreach ($mylist as $ref) {
            $myReferenceNames[$ref->getName()] = true;
        }
        // Is the new reference missing
        foreach ($newReferenceDefs as $refName => $rdef) {
            if (!isset($myReferenceNames[$refName])) {
                $db->addForeignKey($tableName, $defaultSchema, $rdef->makeReferenceClass());
            }
        }
    }

    /**
     * Scan for all versions
     *
     * @param string $dir Directory to scan
     *
     * @return ItemInterface[]
     */
    public static function scanForVersions($dir) {
        $versions = [];
        $iterator = new DirectoryIterator($dir);

        foreach ($iterator as $fileinfo) {
            $filename = $fileinfo->getFilename();
            if (
                    !$fileinfo->isDir() || $fileinfo->isDot() || !VersionCollection::isCorrectVersion($filename)
            ) {
                continue;
            }

            $versions[] = VersionCollection::createItem($filename);
        }

        return $versions;
    }

    /**
     * Drop indexes which will be lost or changed.
     * @param Adapter $db  is the database connection object
     * @param TableDef $tableDef
     */
    static public function dropOldIndexes(Adapter $db, TableDef $tableDef) {
        $indexDef = $tableDef->getIndexDefs();
        $tableName = $tableDef->getName();
        $tableSchema = $tableDef->getSchema();

        $newIndexes = [];
        foreach ($indexDef as $index) {
            $newIndexes[$index->getName()] = $index;
        }

        $myIndexes = [];
        $current = $db->describeIndexes($tableName, $tableSchema);
        foreach ($current as $index) {
            $def = new IndexDef();
            $def->setFromIndex($index);
            $myIndexes[$def->getName()] = $def;
        }

        foreach ($indexDef as $newIndex) {
            $newIndexName = $newIndex->getName();
            if (isset($myIndexes[$newIndexName])) {
                $myIndex = $myIndexes[$newIndexName];
                if (!IndexDef::isEqual($newIndex, $myIndex)) {
                    if ($newIndexName == 'PRIMARY') {
                        $db->dropPrimaryKey($tableName, $tableSchema);
                    } else {
                        $db->dropIndex($tableName, $tableSchema, $newIndexName);
                    }
                }
            }
        }
        foreach ($myIndexes as $indexName => $index) {
            if (!isset($newIndexes[$indexName])) {
                $db->dropIndex($tableName, $tableSchema, $indexName);
            }
        }
    }

    /**
     * Add indexes that are missing.
     * @param Phalcon\Db - A database connection
     * @param TableDef $tableDef
     */
    static public function addNewIndexes(Adapter $db, TableDef $tableDef) {
        $indexDef = $tableDef->getDbIndexes();
        $tableName = $tableDef->getName();
        $tableSchema = $tableDef->getSchema();

        $newIndexes = [];
        foreach ($indexDef as $index) {
            $newIndexes[$index->getName()] = $index;
        }

        $myIndexes = [];
        $current = $db->describeIndexes($tableName, $tableSchema);
        foreach ($current as $index) {
            $myIndexes[$index->getName()] = $index;
        }

        foreach ($indexDef as $newIndex) {
            $newIndexName = $newIndex->getName();

            if (!isset($myIndexes[$newIndexName])) {
                if ($newIndexName == 'PRIMARY') {
                    $db->addPrimaryKey($tableName, $tableSchema, $newIndex);
                } else {
                    $db->addIndex($tableName, $tableSchema, $newIndex);
                }
            }
        }
    }

    /**
     * Create table from the definitions, not including references
     * @param Adapter $db
     * @param TableDef $tdef
     * @return string
     */
    static public function createTable(Adapter $db, TableDef $tdef) {
        $defaultSchema = $tdef->getSchema();
        $tableName = $tdef->getName();
        /** bug fix -- Error duplicate primary key -- if a column has primary = true
         * and index also has a key type of 'PRIMARY' (even if they are the same).
         * DbIndex has no column information for things like 'autoIncrement'
         * and it is not part of index type either.
         * So single column index with Primary takes precedence, over index called 'PRIMARY'.
         * A multi-column index takes precedence over multiple columns with ('primary'=>true)
         */
        $plist = $tdef->getIndexNames();
        $allDefs = [];
        if (isset($plist['PRIMARY'])) {
            $primaryKey = $plist['PRIMARY'];
            if (count($primaryKey->getIndexColumns()) == 1) {
                $defs = $tdef->getColumnsByProperty('primary', true);
                if (count($defs)) {
                    unset($plist['PRIMARY']);
                }
            } else {
                $defs = $tdef->getColumnsByProperty('primary', true);
                foreach ($defs as $cdef) {
                    $cdef->unsetValue('primary');
                }
                $allDefs[] = $primaryKey;
                unset($plist['PRIMARY']);
            }
            foreach ($plist as $key => $object) {
                $allDefs[] = $object;
            }
        }
        $allIndexes = [];
        foreach ($allDefs as $idef) {
            $allIndexes[] = $idef->makeIndexClass();
        }


        $definition = [
            'columns' => $tdef->getDbColumns(),
            'indexes' => $allIndexes,
            'options' => $tdef->getDbOptions()
        ];
        $db->createTable($tableName, $defaultSchema, $definition);
        return 'created table $tableName';
    }

    /**
     * 
     * Modify columns. Assume table exists.
     * @param Adapter $db
     * @param TableDef $tableDef
     * @return string  A description of operations.
     * @throws DbException
     */
    static public function morphTableColumns(Adapter $db, $tableDef) {
        $defaultSchema = $tableDef->getSchema();
        $tableName = $tableDef->getName();

        $newFieldDefs = self::arrayByName($tableDef->getColumnDefs());

        /** @var \Phalcon\Db\ColumnInterface $tableColumn */
// assume table name hasn't changed
        $myFieldDefs = [];
        $myColumns = $db->describeColumns($tableName, $defaultSchema);

//  I want this tables current schema, probably $defaultSchema
        foreach ($myColumns as $col) {
            $cdef = new ColumnDef();
            $cdef->setFromColumn($col, $db->getType());
            $myFieldDefs[$col->getName()] = $cdef;
        }
        $changeCount = 0;
        foreach ($newFieldDefs as $fieldName => $newColumn) {
            if (!isset($myFieldDefs[$fieldName])) {
                self::$db->addColumn($tableName, $defaultSchema, $newColumn->makeColumnClass());
            } else {
                $myColumnDef = $myFieldDefs[$fieldName];

                if (!ColumnDef::isEqual($myColumnDef, $newColumn)) {
                    $db->modifyColumn($tableName, $defaultSchema, $myColumnDef->makeColumnClass(), $newColumn->makeColumnClass());
                    $changeCount += 0;
                }
            }
        }
        return 'changed ' . $changeCount . ' columns';
    }

    /**
     * Cleanout of existing table rows followed by 
     * in-transaction insert of all rows.
     * Most likely to work best at database creation.
     * 
     * @param Adapter $db
     * @param \Schema\TableDef $tdef
     * @param string $fileName
     * @return type
     */
    static public function batchInsert(Adapter $db, TableDef $tdef, string $fileName) {
        if (!file_exists($fileName)) {
            return; // nothing to do
        }
        $tableName = $tdef->getName();
        $db->begin();
        $db->delete($tableName); // delete all the rows, so
        $fields = $tdef->getFieldNames();
        $batchHandler = fopen($fileName, 'r');
        while (($line = fgetcsv($batchHandler)) !== false) {
            $values = array_map(
                    function ($value) {
                return null === $value ? null : stripslashes($value);
            }, $line
            );

            $nullify = new Nullify();
            $db->insert($tableName, $nullify($values), $fields);
            unset($line);
        }
        fclose($batchHandler);
        $db->commit();
    }

    /**
     * Delete the migration datasets from the table
     * This works best on tables with primary keys.
     * @param string $tableName
     */

    /**
     * 
     * @param Adapter $db
     * @param TableDef $tdef
     * @param string $fileName
     * @return type
     * @throws Exception
     */
    static public function batchDelete(Adapter $db, TableDef $tdef, string $fileName) {
        if (!file_exists($fileName)) {
            return; // nothing to do
        }

        $db->begin();
        $db->delete($tableName);
        $batchHandler = fopen($migrationData, 'r');
        $plist = $tdef->getIndexesByType('PRIMARY');
        if (empty($plist) || count($plist) > 1) {
            throw new Exception('Need a primary key for batchDelete in ' . $tdef->getName());
        }
        $primary = $plist[0];
        // Assume datafile has columns in same order as tabledef 
        $columns = $primary->getIndexColumns();
        $allOffsets = $tdef->getColumnOffsets();
        
        $whereClause = '';
        $wct = 0;
        $offsets = [];
        foreach($columns as $col) {
            if ($wct > 0) {
                $whereClause .= ' and ';
            }
            $wct += 1;
            $whereClause .= $col . '=?'; //TODO = quote/escapers for name
            $offsets[] = $allOffsets[$col];
        }
        
        $keyValues = array_fill(0, $wct, null);
        while (($line = fgetcsv($batchHandler)) !== false) {
            $values = array_map(
                    function ($value) {
                return null === $value ? null : stripslashes($value);
            }, $line
            );
            for ($i = 0; $i < $wct; $i++) {
                $keyValues[$i] = $values[ $offsets[$i] ];
            }
            $db->delete($tableName, $whereClause, $keyValues);
            unset($line);
        }
        fclose($batchHandler);
        $db->commit();
    }

    /**
     * Get db connection
     *
     * @return \Phalcon\Db\AdapterInterface
     */
    public function getConnection() {
        return self::$_connection;
    }

}
