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
            'Mod' => PHP_DIR . "/Mod"
        ])
        ->register();
$config = $ctx->config;
$mod->addFile($mod->dir . '/mod_config.xml');


$config->database = Path::getConfig( $config->configDir . '/database.xml');
$config->pcan = Path::getConfig( $config->configDir . '/pcan.xml');

$mod->viewsDir = [
    $mod->viewsDir,
    $config->pcan->pcanDir . DS . 'views' . DS
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
