<?php

/* 
 * @author Michael Rynn
 * Sanitize ths as pcan_sample.php for commits
 */


defined('APP_PATH') || define('APP_PATH', MOD_DIR . 'app'); // for default phalcon-devtools generation

$baseDir = PHP_DIR;
$appDir = MOD_DIR . "app/";
$adminDir = MOD_DIR . "admin/";
$devDir = MOD_DIR . "Dev/";
$apiDir = MOD_DIR . "api/";
$chimpDir = MOD_DIR . "chimp/";
$config_data = [   
    
    "noSSL" => false,
    "contact" => [
        "article" => 'contact-us',
    ],

    "facebook" => [
        'app_id' => "1451259695129905",
        'app_secret' => "8271c720326d3975e8b037e7de3774ec",
        'default_graph_version' => 'v2.9'
    ],
    "google" => [
        'loginCaptcha' => true,
        'signupCaptcha' => true,
        'recaptcha' => true,
        'captchaPublic' => "6Lf9fgkTAAAAAPBH6uixBmZ5BO4iF6QOW17MqlQj",
        'captchaPrivate' => "6Lf9fgkTAAAAAMqlHwJx9PenZMoefrAMnVZgNB5j",
    ],
    'mail' => [
        'templates' => 'emailTemplates',
        'toName' => 'Secretary Parracan',
        'toEmail' => 'parracan@parracan.org',
        'fromName' => 'Contact Form ParraCAN website',
        'fromEmail' => 'parracan@parracan.org',
        'smtp' => [
            'server' => 'mail.parracan.net',
            'port' => 465,
            'security' => 'ssl',
            'username' => 'admin@parracan.net',
            'password' => 'nDAS43'
        ]
    ],
    'vendor' => [
        "incubator" => PHP_DIR . 'vendor/phalcon/incubator/Library/Phalcon/',
        'facebookDir' => PHP_DIR . 'vendor/facebook-sdk/src/Facebook/',
    ],
    "pcan" => [
        "implicitViews" => true,
        "baseDir" => PHP_DIR,
        "cacheDir" => PHP_DIR . "cache/",
        "webDir" => WEB_ROOT,
        "assetJoin" => "assets/prod/",
        "assetSrc" => "assets/src/",
        "pcanDir" => PCAN_DIR,
        'timezone' => 'Australia/Sydney',
        'domainName' => 'parracan.dev',
        'publicUrl' => 'parracan.dev',
        'shortName' => 'ParraCAN',
        "logDir" => PHP_DIR . "log/",
        "errorLog" => PHP_DIR . "log/error.log",
         'logo' => '/image/gallery/site/logo.jpg',
    ],
];
return $config_data;



