<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mod;

defined('VENDOR') || define('VENDOR', PHP_DIR . '/vendor');
$mod = $ctx->activeModule;
(new \Phalcon\Loader)->registerNamespaces([
            $mod->namespace => $mod->dir,
        ])
        ->register();

Path::mergeConfigFile($mod, $mod->dir . '/mod_config.toml');

$config = $ctx->config;
$config['database'] = Path::getConfig( $config['configDir'] . '/database.toml');
$config['pcan'] = Path::getConfig( $config['configDir'] . '/pcan.toml');

$mod->viewsDir = [
    $mod->viewsDir,
    PCAN_DIR . DS . 'views' . DS
];

$ctx->viewService();
$di = $ctx->di;
Setup::commonServices($di);
Setup::dbService($di);

$nullDispatchReg = function($di) { };

/** for each alias of the module, configure the same namespace */
$bogusArray[$mod->name] = $nullDispatchReg;
foreach($config->module_data as $alias => $data) {
    if (is_string($data) && $data === $mod->name) {
        $bogusArray[$alias] = $nullDispatchReg;
    }
}


$ctx->dispatch($mod->namespace . '\Controllers', $bogusArray);
