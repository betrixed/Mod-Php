<?php

/**
 * This bootstrap.php mainly reads a configuration ,
 * sets a few defines and globals
 * and switches to the configured type for next step.
 * @author Michael Rynn
 */
namespace Mod;

(new \Phalcon\Loader())->registerNamespaces(
        ['Mod' => PHP_DIR . "/Mod"]
        )->register();

error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 'On');

define('MOD_DIR', PHP_DIR . '/modules');
define('APP_PATH', MOD_DIR . '/app');

define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DEV_VERSION', '1.0');


$gConfig = [
    'phpDir' => PHP_DIR,
    'cacheDir' => PHP_DIR . '/cache',
    'webDir' => WEB_ROOT,
    'configDir' => PHP_DIR . '/config',
    'configCache' => PHP_DIR . '/cache/config'
];

Path::$config = new XmlConfig();

Path::$config->addArray($gConfig);
Path::$config->addFile($gConfig['configDir'] . '/config.xml');

if (Path::$config->offline) {
    echo ("Sorry, this service is offline.");
    return;
}


try {
    switch (Path::$config->configType) {
        case 'module' :
        default :
            $ctx = new Context();
            $mod_strap = $ctx->init(Path::$config);  
            $ctx->di->setShared('mod', $ctx->activeModule);
            require $mod_strap;
    }
}
catch(\Exception $ex) {
   echo $ex->getMessage(); 
}


    