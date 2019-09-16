<?php

/**
 * @author Michael Rynn
 * 
 * Small set of service initialization functions
 */

namespace Mod;

use Phalcon\Di\DiInterface;
use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream as SessionAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\View;
use Phalcon\Url;
use Phalcon\Mvc\Model\Metadata\Files as MetaData;

const SESSION_TIME_OUT = 4 * 3600;

class Setup {
    /**
     * 
     * @param DiInterface $di
     * @param string $cacheDir
     * 
     * Setup a view service, for volt Engine, 
     * $cacheDir being the directory to write the .php cache files,
     * to set option compiledPath
     */
    static function viewService(DiInterface $di, $mod) {

        $viewsDir = $mod->viewsDir;
        $cacheDir = Path::endSep($mod->voltCache);
        $di->set("view", function () use ($viewsDir) {
            $view = new View();
            $view->setViewsDir($viewsDir);
            $view->registerEngines(array(
                ".volt" => 'volt'
            ));

            return $view;
        }
        );

        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        
        $di->setShared('volt', function ($view, $di) use ($cacheDir) {

            $volt = new VoltEngine($view, $di);

            $volt->setOptions(array(
                "compiledPath" => $cacheDir,
                'compiledSeparator' => '_',
                'compileAlways' => false
            ));

            //$compiler = $volt->getCompiler();
            //$compiler->addFunction('is_a', 'is_a');

            return $volt;
        });
    }

    static function dbService(DiInterface $di) {
        $config = $di->get('config');
        $database = $config->database;
        $di->setShared('db', function () use ($database) {
            $adapter = $database->adapter ?? 'Mysql';
            $adapter = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

            $connection = new $adapter([
                "host" => $database->host,
                "username" => $database->username,
                "password" => $database->password,
                "dbname" => $database->dbname,
                "charset" => $database->charset
            ]);
            //'$connection->setEventsManager($em);

            return $connection;
        });
    }

    /**
     * 
     * @param DiInterface $di
     * Setup flash, id (SecurityPlugin), db, session, elements (plugin), cart, modelsMetadata, url, mail
     */
    static function commonServices(DiInterface $di) {

        $config = $di->get('config');

        $di->setShared('session', function () {

            ini_set('session.gc_maxlifetime', SESSION_TIME_OUT);
            session_set_cookie_params(SESSION_TIME_OUT);

            $session = new Manager();
            $files = new SessionAdapter(['savePath' => '/tmp']);
            $session->setHandler($files);
            //$session->start();
            return $session;
        });

        $di->setShared('elements', function () {
            return new Plugins\Elements();
        });

        $baseURI = $config->application->baseURI ?? '';

        $di->setShared("url", function () use($baseURI) {
            $url = new Url();

            $url->setBaseUri($baseURI);

            return $url;
        });

        $di->setShared('elements', function () {
            return new \Mod\Plugins\Elements();
        });
        
        $metaDir = Path::endSep($config->metaDir);

        $di->setShared('modelsMetadata', function () use($metaDir) {
            return new MetaData(
                    ["metaDataDir" => $metaDir]
            );
        });
        
        $di->set('flash', function(){
            return new \Phalcon\Flash\Session([
            'error' => 'alert alert-error',
            'success' => 'alert alert-success',
            'notice' => 'alert alert-info',
        ]);
    });
    }
}
