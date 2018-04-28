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
  |          Serghei Iakovlev <serghei@phalconphp.com>                     |
  +------------------------------------------------------------------------+
 */

namespace Mod\Schema;

use Phalcon\Script\Color;

use Phalcon\Script\ScriptException;

use Phalcon\Mvc\Model\Exception as ModelException;
//use Phalcon\Mvc\Model\Migration as ModelMigration;
use Mod\Schema\Migrate as ModelMigration;

use Phalcon\Version\ItemCollection as VersionCollection;
use Phalcon\Console\OptionStack;
use Phalcon\Mvc\Model\Migration\TableAware\ListTablesIterator;
use Phalcon\Mvc\Model\Migration\TableAware\ListTablesDb;
use Phalcon\Config\Path;

/**
 * Migrations Class
 *
 * @package Phalcon
 */
class Migrations {

    /**
     * Check if the script is running on Console mode
     *
     * @return boolean
     */
    public static function isConsole() {
        return PHP_SAPI === 'cli';
    }

    /**
     * Generate migrations
     *
     * @param array $options
     *
     * @throws \Exception
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public static function generate(array $options) {
        $optionStack = new OptionStack();
        $listTables = new ListTablesDb();
        $optionStack->setOptions($options);
        $optionStack->setDefaultOption('version', null);
        $optionStack->setDefaultOption('descr', null);
        $optionStack->setDefaultOption('noAutoIncrement', null);
        $optionStack->setDefaultOption('verbose', false);

        // Migrations directory
        if ($optionStack->getOption('migrationsDir') && !file_exists($optionStack->getOption('migrationsDir'))) {
            mkdir($optionStack->getOption('migrationsDir'), 0755, true);
        }

        $versionItem = $optionStack->getVersionNameGeneratingMigration();

        // Path to migration dir
        $migrationPath = rtrim($optionStack->getOption('migrationsDir'), '\\/') . DIRECTORY_SEPARATOR . $versionItem->getVersion();
        if (!file_exists($migrationPath)) {
            if (is_writable(dirname($migrationPath)) && !$optionStack->getOption('verbose')) {
                mkdir($migrationPath);
            } elseif (!is_writable(dirname($migrationPath))) {
                throw new \RuntimeException("Unable to write '{$migrationPath}' directory. Permission denied");
            }
        } elseif (!$optionStack->getOption('force')) {
            throw new \LogicException('Version ' . $versionItem->getVersion() . ' already exists');
        }
        $config = $optionStack->getOption('config');
        // Try to connect to the DB
        if (!isset($config->database)) {
            throw new \RuntimeException('Cannot load database configuration');
        }

        $morphus = new SchemaConfig($optionStack->getOptions());
        $morphus->setup();

        $exportOption = $morphus->exportData;
        $wasMigrated = false;
        if ($morphus->tableName === '@') {

            $migrations = $morphus->generateAll($versionItem);
            $outputDir = Path::endSep($migrationPath);
            foreach ($migrations as $tableName => $tdef) {
                if ($tableName === $morphus->logTableName) {
                    continue;
                }
                if ($exportOption == 'oncreate') {
                    $dataFile = $outputDir . $tableName . '.dat';
                    $exportedRows = $morphus->exportDataAsCSV($tdef, $dataFile);
                    $tdef->setImportRows($exportedRows);
                }
                $tableFile = $outputDir . $tableName . '.toml';
                $wasMigrated = $tdef->saveTOML($tableFile) || $wasMigrated;
            }
        } else {
            $prefix = $optionStack->getPrefixOption($optionStack->getOption('tableName'));

            if (!empty($prefix)) {
                $optionStack->setOption('tableName', $listTables->listTablesForPrefix($prefix));
            }

            if ($optionStack->getOption('tableName') == '') {
                print Color::info('No one table is created. You should create tables first.') . PHP_EOL;
                return;
            }

            $tables = explode(',', $optionStack->getOption('tableName'));

            foreach ($tables as $table) {
                $migration = ModelMigration::generate($versionItem, $table, $optionStack->getOption('exportData'));
                if (!$optionStack->getOption('verbose')) {
                    $tableFile = $migrationPath . DIRECTORY_SEPARATOR . $table . '.toml';
                    $wasMigrated = $migration->saveTOML($tableFile) || $wasMigrated;
                }
            }
        }

        if (self::isConsole() && $wasMigrated) {
            print Color::success('Version ' . $versionItem->getVersion() . ' was successfully generated') . PHP_EOL;
        } elseif (self::isConsole() && !$optionStack->getOption('verbose')) {
            print Color::info('Nothing to generate. You should create tables first.') . PHP_EOL;
        }
    }

    /**
     * Run migrations
     *
     * @param array $options
     *
     * @throws Exception
     * @throws ModelException
     * @throws ScriptException
     *
     */
    public static function run(array $options) {
        $optionStack = new OptionStack();
        $listTables = new ListTablesIterator();
        $optionStack->setOptions($options);
        $optionStack->setDefaultOption('verbose', false);

        // Define versioning type to be used
        if (isset($options['tsBased']) && $optionStack->getOption('tsBased') === true) {
            VersionCollection::setType(VersionCollection::TYPE_TIMESTAMPED);
        } else {
            VersionCollection::setType(VersionCollection::TYPE_INCREMENTAL);
        }

        $migrationsDir = rtrim($optionStack->getOption('migrationsDir'), '\\/');
        if (!file_exists($migrationsDir)) {
            throw new ModelException('Migrations directory was not found.');
        }
        $optionStack->setOption('migrationsDir', $migrationsDir);

        if (!$optionStack->getOption('config') instanceof \Phalcon\Config) {
            throw new ModelException('Internal error. Config should be an instance of ' . Config::class);
        }

        // Init ModelMigration
        if (!isset($optionStack->getOption('config')->database)) {
            throw new ScriptException('Cannot load database configuration');
        }

        $finalVersion = null;
        if (isset($options['version']) && $optionStack->getOption('version') !== null) {
            $finalVersion = VersionCollection::createItem($options['version']);
        }

        $optionStack->setOption('tableName', $options['tableName'], '@');

        $versionItems = ModelMigration::scanForVersions($migrationsDir);

        if (!isset($versionItems[0])) {
            if (php_sapi_name() == 'cli') {
                fwrite(STDERR, PHP_EOL . 'Migrations were not found at ' . $migrationsDir . PHP_EOL);
                exit;
            } else {
                throw new ModelException('Migrations were not found at ' . $migrationsDir);
            }
        }

        // Set default final version
        if ($finalVersion === null) {
            $finalVersion = VersionCollection::maximum($versionItems);
        }
        $morphus = new SchemaConfig($optionStack->getOptions());
        $morphus->setup();

        /** @var \Phalcon\Version\IncrementalItem $initialVersion */
        $initialVersion = $morphus->getCurrentVersion();
        $completedVersions = $morphus->getCompletedVersions();

        // Everything is up to date
        if ($initialVersion->getStamp() === $finalVersion->getStamp()) {
            print Color::info('Everything is up to date');
            exit(0);
        }

        $direction = ModelMigration::DIRECTION_FORWARD;
        if ($finalVersion->getStamp() < $initialVersion->getStamp()) {
            $direction = ModelMigration::DIRECTION_BACK;
        }

        if (ModelMigration::DIRECTION_FORWARD === $direction) {
            // If we migrate up, we should go from the beginning to run some migrations which may have been missed
            $versionItemsTmp = VersionCollection::sortAsc(array_merge($versionItems, [$initialVersion]));
            $initialVersion = $versionItemsTmp[0];
        } else {
            // If we migrate downs, we should go from the last migration to revert some migrations which may have been missed
            $versionItemsTmp = VersionCollection::sortDesc(array_merge($versionItems, [$initialVersion]));
            $initialVersion = $versionItemsTmp[0];
        }

        // Run migration
        $versionsBetween = VersionCollection::between($initialVersion, $finalVersion, $versionItems);
        $prefix = $optionStack->getPrefixOption($optionStack->getOption('tableName'));

        /** @var \Phalcon\Version\IncrementalItem $versionItem */
        foreach ($versionsBetween as $versionItem) {

            // If we are rolling back, we skip migrating when initialVersion is the same as current
            if ($initialVersion->getVersion() === $versionItem->getVersion() && ModelMigration::DIRECTION_BACK === $direction) {
                continue;
            }

            if ((ModelMigration::DIRECTION_FORWARD === $direction) && isset($completedVersions[(string) $versionItem])) {
                print Color::info('Version ' . (string) $versionItem . ' was already applied');
                continue;
            } elseif ((ModelMigration::DIRECTION_BACK === $direction) && !isset($completedVersions[(string) $initialVersion])) {
                print Color::info('Version ' . (string) $initialVersion . ' was already rolled back');
                $initialVersion = $versionItem;
                continue;
            }

            if ($initialVersion->getVersion() === $finalVersion->getVersion() && ModelMigration::DIRECTION_BACK === $direction) {
                break;
            }

            $migrationStartTime = date("Y-m-d H:i:s");
            // Directory depends on Forward or Back Migration
            $tableDefDir = $migrationsDir . DS . (ModelMigration::DIRECTION_BACK === $direction ? $initialVersion->getVersion() : $versionItem->getVersion());


            
            
            if ($optionStack->getOption('tableName') === '@') {
                $morphus->runAllTables($tableDefDir);
            } else {
                if (!empty($prefix)) {
                    $optionStack->setOption('tableName', $listTables->listTablesForPrefix($prefix, $iterator));
                }
                $tables = explode(',', $optionStack->getOption('tableName'));
                foreach ($tables as $tableName) {
                    ModelMigration::migrate($initialVersion, $versionItem, $tableName);
                }
            }

            if (ModelMigration::DIRECTION_FORWARD == $direction) {
                $morphus->addCurrentVersion((string) $versionItem, $migrationStartTime);
                print Color::success('Version ' . $versionItem . ' was successfully migrated');
            } else {
                $morphus->removeCurrentVersion((string) $initialVersion);
                print Color::success('Version ' . $initialVersion . ' was successfully rolled back');
            }

            $initialVersion = $versionItem;
        }
    }

    /**
     * List migrations along with statuses
     *
     * @param array $options
     *
     * @throws Exception
     * @throws ModelException
     * @throws ScriptException
     *
     */
    public static function listAll(array $options) {
        // Define versioning type to be used
        if (true === $options['tsBased']) {
            VersionCollection::setType(VersionCollection::TYPE_TIMESTAMPED);
        } else {
            VersionCollection::setType(VersionCollection::TYPE_INCREMENTAL);
        }

        $migrationsDir = rtrim($options['migrationsDir'], '/');
        if (!file_exists($migrationsDir)) {
            throw new ModelException('Migrations directory was not found.');
        }
        $options->setOption('migrationsDir', $migrationsDir);

        /** @var Config $config */
        $config = $options['config'];
        if (!$config instanceof Config) {
            throw new ModelException('Internal error. Config should be an instance of ' . Config::class);
        }

        // Init ModelMigration
        if (!isset($config->database)) {
            throw new ScriptException('Cannot load database configuration');
        }

        $versionItems = ModelMigration::scanForVersions($migrationsDir);

        if (!isset($versionItems[0])) {
            print Color::info('Migrations were not found at ' . $migrationsDir);
            return;
        }

        $morphus = new SchemaConfig($options->getOptions());
        $morphus->setup();

        $sortedVersions = VersionCollection::sortDesc($versionItems);
        $completedVersions = $morphus->getCompletedVersions();

        $versionColumnWidth = 27;
        foreach ($sortedVersions as $versionItem) {
            if (strlen($versionItem) > ($versionColumnWidth - 2)) {
                $versionColumnWidth = strlen($versionItem) + 2;
            }
        }
        $format = "│ %-" . ($versionColumnWidth - 2) . "s │ %12s │";

        $report = [];
        foreach ($sortedVersions as $versionItem) {
            $versionNumber = $versionItem->getVersion();
            $report[] = sprintf($format, $versionNumber, isset($completedVersions[$versionNumber]) ? 'Y' : 'N');
        }

        $header = sprintf($format, 'Version', 'Was applied');
        $report[] = '├' . str_repeat('─', $versionColumnWidth) . '┼' . str_repeat('─', 14) . '┤';
        $report[] = $header;

        $report = array_reverse($report);

        echo '┌' . str_repeat('─', $versionColumnWidth) . '┬' . str_repeat('─', 14) . '┐' . PHP_EOL;
        echo join(PHP_EOL, $report) . PHP_EOL;
        echo '└' . str_repeat('─', $versionColumnWidth) . '┴' . str_repeat('─', 14) . '┘' . PHP_EOL . PHP_EOL;
    }

}
