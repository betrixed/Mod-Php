<?php

/**
 * This bootstrap.php mainly reads a configuration ,
 * sets a few defines and globals
 * and switches to the configured type for next step.
 * @author Michael Rynn
 */
error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 'On');

define('MOD_DIR', PHP_DIR . '/modules');
define('APP_PATH', MOD_DIR . '/app');

define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DEV_VERSION', '1.0');

use Mod\Path;
use Phalcon\Loader;

(new Loader())->registerNamespaces([
    'Mod' => PHP_DIR . '/Mod',
])->register();

$gPaths = [
    'phpDir' => PHP_DIR,
    'cacheDir' => PHP_DIR . '/cache',
    'webDir' => WEB_ROOT,
    'configDir' => PHP_DIR . '/config'
];

Path::$config = new \Pun\KeyTable($gPaths);
Path::mergeConfigFile(Path::$config, $gPaths['configDir'] . '/config.toml');

if (Path::$config->offline) {
    echo "Sorry, this service is offline.";
    return;
}

try {
    switch (Path::$config->configType) {
        case 'module' :

        default :
            require 'runModule.php';
    }
}
catch(\Exception $ex) {
   echo $ex->getMessage(); 
}


    