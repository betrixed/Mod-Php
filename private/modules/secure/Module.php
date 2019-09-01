<?php

namespace Secure;

use Secure\Plugins\Security;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

class Module implements ModuleDefinitionInterface
{
    static function acl(DiInterface $di) {
        $di->setShared('acl', function() {
            $splug = new Security();
            return $splug;
        });
    }
    
    /**
     * Register a specific autoloader for the module
     */
    public function registerAutoloaders(DiInterface $di = null)
    {
        //$mod = $di->get('modules')->admin;
        //\Pcan\mod_LoaderService($di, [$mod->namespace => $mod->dir ]);
    }

    /**
     * Register specific services for the module
     */
    public function registerServices(DiInterface $di)
    {
        self::acl($di);
    }
}


