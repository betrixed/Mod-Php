<?php

/** Return a PHP array */
/* 
 * PHP_DIR, WEB_ROOT, ROOT_DIR, APP_PATH paths not ending in Directory Separator
 * must be defined already
 */
return [
    'configType' => "module",
    'configPath' => PHP_DIR . '/config',
    'defaultModule' => "app",
    'modulesDir' => PHP_DIR . '/modules',
    'modulesBootstrap' => 'mod_bootstrap.php',
    'timezone' => 'Australia/Sydney',
    'logErrors' => true,
    'errorLog' => PHP_DIR . '/log/error.log',
    'offline' => false,
    'loader' => [ 
        'Pcan' => PHP_DIR . '/Pcan', 
        //'Phalcon' => PHP_DIR . '/vendor/phalcon/incubator/Library/Phalcon' 
    ],

   
    
    'module_data' => [
        
        /*'modtools' => [
            'dir' => PHP_DIR . '/vendor/phalcon/devtools/scripts/Phalcon/Web/ModTools',
            'namespace' => 'ModTools',
            'bootstrap' => 'tools_bootstrap.php'
        ],
        'define' => [
            'PTOOLSPATH' => PHP_DIR .  '/vendor/phalcon/devtools',
            'BASE_PATH' => ROOT_DIR . '/private' 
        ],
         * 
         */
        'app' => [
            'dir' => PHP_DIR . '/modules/app',
            'namespace' => 'Mod\App',
            'bootstrap' => 'app_bootstrap.php'
        ],
        
        'admin' => [
            'namespace' => 'Mod\Admin',
            'bootstrap' => 'admin_bootstrap.php'
        ],
        
        'id' => 'secure',
        'secure' => [
            'namespace' => 'Secure',
            'bootstrap' => 'secure_bootstrap.php',
            'services' => [
                'acl'
            ]
        ]
        
    ],
    'application' => [
        'modelsDir' => APP_PATH . "/models",
        'appDir' => APP_PATH ,
        'controllersDir' => APP_PATH . '/Controllers',
        'migrationsDir' => PHP_DIR . '/setup/db',
        'viewsDir' => APP_PATH . '/views',
        'pluginsDir' => APP_PATH . '/plugins',
        'libraryDir' => APP_PATH . '/library',
        'cacheDir' => PHP_DIR . '/cache',
        
        'baseURI' => '/',
        'metaDataDir' => PHP_DIR. '/cache/metadata',
    ]
];