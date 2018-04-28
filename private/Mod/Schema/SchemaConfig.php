<?php

/**
 * @author Michael Rynn
 */

namespace Mod\Schema;

use Phalcon\Db\Dialect\MysqlExtended;
use Phalcon\Db\Adapter\Pdo\MysqlExtended as AdapterMysqlExtended;
use Phalcon\Db\Dialect\PostgresqlExtended;
use Phalcon\Db;
use Mod\Path;
use Phalcon\Db\Adapter\Pdo\PostgresqlExtended as AdapterPostgresqlExtended;
use Phalcon\Utils;
use Phalcon\Version\ItemCollection as VersionCollection;

/**
 * Migraction Helper Class
 * Has formerly static components of Migration
 */
class SchemaConfig {

    /**
     * name of the migration table
     */
    const MIGRATION_LOG_TABLE = 'phalcon_migrations';

    /**
     * Migration database connection
     * @var \Phalcon\Db\AdapterInterface
     */
    protected $_connection;

    /**
     * Database configuration
     * @var \Phalcon\Config
     */
    private $_databaseConfig;

    /**
     * Path where to save the migration
     * @var string
     */
    private $_migrationPath = null;

    /** Schema set from connection data */
    private $_defaultSchema;

    /**
     * Skip auto increment
     * @var bool
     */
    private $_skipAI = false;

    /**
     * Version of the migration file
     *
     * @var string
     */
    protected $_version = null;
    protected $_adapter = null;
    protected $tabledDefs;

    /**
     * Filename or db connection to store migrations log
     * @var mixed|Adapter\Pdo
     */
    protected $_storage;
    // true if storage is database table, false if a text file
    protected $_isDbLog;
    protected $_options; // all options copied from optionsStack object

    public function __get($name) {
        return isset($this->_options[$name]) ? $this->_options[$name] : null;
    }

    public function get($name, $default) {
        return isset($this->_options[$name]) ? $this->_options[$name] : $default;
    }

    public function __construct(array $options) {
        $this->_options = $options;
    }

    public function getAdapter() {
        return $this->_adapter;
    }

    /**
     * Prepares database connection
     *
     * @param \Phalcon\Config $database Database config
     * @param bool $verbose array with settings
     * @since 3.2.1 Using Postgresql::describeReferences and PostgresqlExtended dialect class
     *
     * @throws \Phalcon\Db\Exception
     */
    public function setup() {
        $config = $this->_options['config'];
        $database = $config->database;

        if (!isset($this->_options['logTableName'])) {
            $this->_options['logTableName'] = self::MIGRATION_LOG_TABLE;
        }
        $this->setMigrationPath($this->migrationDir);

        if (!isset($database->adapter)) {
            throw new DbException('Unspecified database Adapter in your configuration!');
        }
        $this->_databaseConfig = $database;

        /**
         * The original Phalcon\Db\Adapter\Pdo\Mysql::addForeignKey is broken until the v3.2.0
         *
         * @see: Phalcon\Db\Dialect\MysqlExtended The extended and fixed dialect class for MySQL
         */
        if ($database->adapter == 'Mysql') {
            $adapter = AdapterMysqlExtended::class;
            $this->_adapter = $database->adapter;
        } elseif ($database->adapter == 'Postgresql') {
            $adapter = AdapterPostgresqlExtended::class;
            $this->_adapter = $database->adapter;
        } else {
            $adapter = '\\Phalcon\\Db\\Adapter\\Pdo\\' . $database->adapter;
            $this->_adapter = $database->adapter;
        }


        if (!class_exists($adapter)) {
            throw new DbException("Invalid database adapter: '{$adapter}'");
        }


        $configArray = $database->toArray();
        unset($configArray['adapter']);
        $this->_connection = new $adapter($configArray);
        $this->_databaseConfig = $database;

        $this->_defaultSchema = Utils::resolveDbSchema($database);
        //Connection custom dialect Dialect/MysqlExtended
        if ($database->adapter == 'Mysql') {
            $this->_connection->setDialect(new MysqlExtended);
        }

        //Connection custom dialect Dialect/PostgresqlExtended
        if ($database->adapter == 'Postgresql') {
            $this->_connection->setDialect(new PostgresqlExtended);
        }

        if (Migrations::isConsole() && $this->verbose) {
            $eventsManager = new EventsManager();

            $eventsManager->attach(
                    'db', new DbProfilerListener()
            );

            $this->_connection->setEventsManager($eventsManager);
        }
        $this->useVersionLog();
    }

    private function createLogTable() {
        return $this->_storage->createTable($this->logTableName, null, [
                    'columns' => [
                        new Column(
                                'version', [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 255,
                            'notNull' => true,
                                ]
                        ),
                        new Column(
                                'start_time', [
                            'type' => Column::TYPE_TIMESTAMP,
                            'notNull' => true,
                            'default' => 'CURRENT_TIMESTAMP',
                                ]
                        ),
                        new Column(
                                'end_time', [
                            'type' => Column::TYPE_TIMESTAMP,
                            'notNull' => true,
                                ]
                        )
                    ],
                    'indexes' => [
                        new Index('idx_' . $this->logTableName . '_version', ['version'])
                    ]
        ]);
    }

    /**
     * Initialize migrations log storage
     *
     * @param array $options Applications options
     * @throws DbException
     */
    public function useVersionLog() {
        if ($this->_storage) {
            return;
        }
        $this->_isDbLog = (bool) $this->get('migrationsInDb', false);
        if ($this->_isDbLog) {
            // we already have 
            $database = $this->_databaseConfig;

            $adapter = '\\Phalcon\\Db\\Adapter\\Pdo\\' . $database->adapter;

            if (!class_exists($adapter)) {
                throw new DbException('Invalid database Adapter!');
            }

            $configArray = $database->toArray();
            unset($configArray['adapter']);
            $this->_storage = new $adapter($configArray);

            if ($database->adapter === 'Mysql') {
                // Why would query work? exec?
                // what about postgres
                $this->_storage->exec('SET FOREIGN_KEY_CHECKS=0');
            }

            if (!$this->_storage->tableExists($this->logTableName)) {
                $this->createLogTable();
            }
        } else {
            $directory = $this->directory;
            if (empty($directory)) {
                $path = defined('BASE_PATH') ? BASE_PATH : defined('APP_PATH') ? dirname(APP_PATH) : '';
                $path = rtrim($path, '\\/') . '/.phalcon';
            } else {
                $path = rtrim($directory, '\\/') . '/.phalcon';
            }
            if (!is_dir($path) && !is_writable(dirname($path))) {
                throw new \RuntimeException("Unable to write '{$path}' directory. Permission denied");
            }
            if (is_file($path)) {
                unlink($path);
                mkdir($path);
                chmod($path, 0775);
            } elseif (!is_dir($path)) {
                mkdir($path);
                chmod($path, 0775);
            }

            $this->_storage = $path . '/migration-version';

            if (!file_exists($this->_storage)) {
                if (!is_writable($path)) {
                    throw new \RuntimeException("Unable to write '" . $this->_storage . "' file. Permission denied");
                }
                touch($this->_storage);
            }
        }
    }

    /**
     * 
     * @return type
     */
    public function getCurrentVersion() {
        if ($this->_isDbLog) {
            /** @var AdapterInterface $connection */
            $connection = $this->_storage;
            $lastGoodMigration = $connection->query('SELECT * FROM ' . self::MIGRATION_LOG_TABLE . ' ORDER BY version DESC LIMIT 1');
            if (0 == $lastGoodMigration->numRows()) {
                return VersionCollection::createItem(null);
            } else {
                $lastGoodMigration = $lastGoodMigration->fetchArray();

                return VersionCollection::createItem($lastGoodMigration['version']);
            }
        } else {
            // Get and clean migration
            $version = file_exists($this->_storage) ? file_get_contents($this->_storage) : null;
            if (!empty($version) && !empty(($version = trim($version)))) {
                $version = preg_split('/\r\n|\r|\n/', $version, -1, PREG_SPLIT_NO_EMPTY);
                natsort($version);
                $version = array_pop($version);
            }

            return VersionCollection::createItem($version);
        }
    }

    /**
     * Add migration version to log
     *
     * @param array $options Applications options
     * @param string $version Migration version to store
     * @param string $startTime Migration start timestamp
     */
    public function addCurrentVersion($version, $startTime = null) {
        if ($startTime === null) {
            $startTime = date("Y-m-d H:i:s");
        }
        $endTime = date("Y-m-d H:i:s");

        if ($this->_isDbLog) {
            /** @var AdapterInterface $connection */
            $connection = $this->_storage;
            $connection->insert($this->logTableName, [$version, $startTime, $endTime], ['version', 'start_time', 'end_time']);
        } else {
            $allVersions = $this->getCompletedVersions();
            $allVersions[$version] = 1;
            $currentVersions = array_keys($allVersions);
            sort($currentVersions);
            file_put_contents($this->_storage, implode("\n", $currentVersions));
        }
    }

    /**
     * Remove migration version from log
     *
     * @param array $options Applications options
     * @param string $version Migration version to remove
     */
    public function removeCurrentVersion($version) {
        if ($this->_isDbLog) {
            $this->_storage->execute('DELETE FROM ' . self::MIGRATION_LOG_TABLE
                    . ' WHERE version=\'' . $version . '\'');
        } else {
            $allVersions = $this->getCompletedVersions($options);
            unset($allVersions[$version]);
            $currentVersions = array_keys($allVersions);
            sort($currentVersions);
            file_put_contents($this->_storage, implode("\n", $currentVersions));
        }
    }

    /**
     * Scan $_storage for all completed versions
     *
     * @param array $options Applications options
     * @return array
     */
    public function getCompletedVersions() {
        if ($this->_isDbLog) {
            $rows = $this->_storage->query('SELECT version FROM '
                            . self::MIGRATION_LOG_TABLE
                            . ' ORDER BY version DESC')->fetchAll();
            $completedVersions = array_map(function ($version) {
                return $version['version'];
            }, $rows);
        } else {
            $completedVersions = file($this->_storage, FILE_IGNORE_NEW_LINES);
        }

        return array_flip($completedVersions);
    }

    /**
     * Set the skip auto increment value
     *
     * @param bool $skip
     */
    public function setSkipAutoInc($skip) {
        $this->_skipAI = $skip;
    }

    public function getSkipAutoInc() {
        return $this->_skipAI;
    }

    /**
     * Set the migration directory path
     *
     * @param string $path
     */
    public function setMigrationPath($path) {
        $this->_migrationPath = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
    }

    public function getMigrationPath() {
        return $this->_migrationPath;
    }
    /**
     * Return live database connection
     * @return \Phalcon\Db\Adapter
     */
    public function getConnection() {
        return $this->_connection;
    }

    /* $fileHandler = fopen(self::$_migrationPath . $version->getVersion() . '/' . $table . '.dat', 'w');
     */

    /**
     * 
     * @param type $tableDef
     * @param type $fileName
     * @return int
     */
    public function exportDataAsCSV($tableDef, $fileName) {
        $db = $this->getConnection();
        $escapedName = $db->escapeIdentifier($tableDef->getName());
        $cursor = $db->query('SELECT * FROM ' . $escapedName);
        $cursor->setFetchMode(Db::FETCH_ASSOC);
        $importRows = 0;

        $fieldSet = $tableDef->getFieldDataTypes();
        $numberTypes = ['INTEGER', 'BIGDECIMAL'];
        
        $fileHandler = fopen($fileName,'w');
        
        while ($row = $cursor->fetchArray()) {
            $data = [];
            $importRows += 1;
            foreach ($row as $key => $value) {
                if (in_array($fieldSet[$key], $numberTypes)) {
                    if ($value === '' || is_null($value)) {
                        $data[] = 'NULL';
                    } else {
                        $data[] = addslashes($value);
                    }
                } else {
                    $data[] = is_null($value) ? "NULL" : addslashes($value);
                }
                unset($value);
            }
            fputcsv($fileHandler, $data);
            unset($row);
            unset($data);
        }
        fclose($fileHandler);
        return $importRows;
    }

    /**
     * Create a TableDef object
     *
     * @param ItemInterface $version
     * @param string        $table
     *
     * @return TableDef
     * @throws \Phalcon\Db\Exception
     */
    public function createTableDef($table) {
        $oldColumn = null;

        $tdef = new TableDef();

        $defaultSchema = $this->getSchema();

        $tdef->name = $table;
        $tdef->schema = $defaultSchema;
        $db = $this->getConnection();

        $description = $db->describeColumns($table, $defaultSchema);

        foreach ($description as $field) {
            /** @var \Phalcon\Db\ColumnInterface $field */
            $colDef = $tdef->addColumnDef($field, $this->getAdapter());

            $colName = $field->getName();

            if ($oldColumn != null) {
                $colDef->setValue('after', $oldColumn);
            } else {
                $colDef->setValue('first', true);
            }
            $oldColumn = $colName;
        }

        $indexes = $db->describeIndexes($table, $defaultSchema);
        foreach ($indexes as $indexName => $dbIndex) {
            $tdef->addIndexDef($dbIndex);
        }


        $references = $db->describeReferences($table, $defaultSchema);
        foreach ($references as $constraintName => $dbReference) {
            $tdef->addReferenceDef($dbReference);
        }

        $tableOptions = $db->tableOptions($table, $defaultSchema);
        foreach ($tableOptions as $optionName => $optionValue) {
            $optionName = strtoupper($optionName);
            $tdef->setOption($optionName, $optionValue);
        }

        return $tdef;
    }

    public function getSchema() {
        return $this->_defaultSchema;
    }

    public function generateAll($version) {
        $schema = $this->getSchema();
        $tableNames = $this->getConnection()->listTables($schema);
        $this->tableDefs = [];
        $defs = & $this->tableDefs;
        $sVersion = (string) $version;
        foreach ($tableNames as $tname) {
             $tdef = $this->createTableDef($tname);
             $tdef->setVersion($sVersion);
             $defs[$tname] = $tdef;
        }
        return $this->tableDefs;
    }

    /**
     * Apply the table definitions to the database.
     * applying a schema is a many - tabled , multi-phased thing, 
     * please so sort out versions and direction
     * before coming here.
     * @param string $tableDefDir
     * @param Version $initialVersion
     * @param Version $versionItem
     */
    public function runAllTables(string $tableDefDir) {
        // 
        $versionPath = Path::endSep($tableDefDir);
        $tableDefList = Migrate::getTableDefList($versionPath);
        $this->versionPath = $versionPath;
        $phases = ['drop-ref', 'drop-index', 'alter-table', 'add-index', 'add-ref'];
        foreach ($phases as $phase) {
            // TODO = call event handler for pre-phase 
            foreach ($tableDefList as $tname => $tdef) {
                // TODO = call event handler for pre- table - phase
                $this->migrate($phase, $tdef);
                // TODO = call event handler for post - table - phase
            }
            // TODO = call event handler for post-phase 
        }
    }

    /**
     * Apply difference between defintions and existing schema.
     * @param string $phase - order of 'drop-*', 'alter-table' , 'add-*'
     * @param \Toml\TableDef $tdef
     * 
     */
    public function migrate($phase, $tdef) {
        $db = $this->getConnection();

        $tableSchema = $tdef->getSchema();
        $toSchema = $this->_defaultSchema;

        if ($toSchema != $tableSchema) {
            $tdef->setSchema($toSchema);
            $refs = $tdef->getReferenceDefs();
            foreach($refs as $ref) {
                $ref->setValue('referencedSchema', $toSchema);
            }
        }
        $tableName = $tdef->getName();
        $tableExists = $db->tableExists($tableName, $tdef->getSchema());

        switch ($phase) {
            case 'drop-ref':
                if ($tableExists) {
                    Migrate::dropOldReferences($db, $tdef);
                }
                break;
            case 'drop-index':
                if ($tableExists) {
                    Migrate::dropOldIndexes($db, $tdef);
                }
                break;
            // case 'drop-table': // leave for post-migration hooks
            // break;  // Can't really drop unknown tables
            case 'alter-table':
                if ($tableExists) {
                    Migrate::morphTableColumns($db, $tdef);
                }
            case 'add-index' :
                if ($tableExists) {
                    Migrate::addNewIndexes($db, $tdef);
                } else {
                    Migrate::createTable($db, $tdef);
                    if ($tdef->getImportRows() > 0) {
                        $path = $this->versionPath . $tdef->getName() . '.dat';
                        Migrate::batchInsert($db, $tdef, $path);
                    }
                }
                break;
            case 'add-ref':
                if ($tableExists) {
                    Migrate::addNewReferences($db, $tdef);
                }
                break;
        }
    }

}
