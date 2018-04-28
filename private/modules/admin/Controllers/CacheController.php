<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Admin\Controllers;

use Mod\Path;
/**
 * Allow administrators to clear various data caches.
 * 
 * @author michael
 */
class CacheController extends \Phalcon\Mvc\Controller {
    //put your code here
    protected function fwdDashboard($msg)
    {
        //$this->pickView('dashboard/index');
        $this->flash->success( $msg );
        $dispatcher = $this->di->get('dispatcher');
        $dispatcher->forward(['controller' => 'dashboard',
                                    'action' => 'index']);
    }
    
    protected function doEachModule($modfn) {
        $modules = $this->modules;      
        // only one module is likely to be fully dynamic configured
        $ctx = $this->ctx;
        
        foreach($modules as $key => $cmod)
        {
            if (is_object($cmod) || is_array($cmod)) {
                $dmod = $ctx->getModuleConfig($key); // get the full dynamic configuration
            }
            else {
                continue;
            }
            
            $modfn($dmod);
        }         
    }
    
    protected function clearMenuCache()
    {
        $baseDir = Path::$config->cacheDir;
        Path::deleteAllFiles($baseDir . "menu_*.dat");
    }
    

    protected function clearVoltCache()
    {
        // problematique - original config values may include string (alias), and arrays
        // only process arrays
        $cacheDir = Path::$config->cacheDir;
        
        $this->doEachModule(function ($dmod) use ($cacheDir) {
            $baseDir = $dmod->voltCache;
            
            if (Path::startsWith($baseDir, $cacheDir)) {
                Path::deleteAllFiles($baseDir . "*.php");
            }
        });
    }
    
    protected function clearAssetCache()
    {
        $baseDir = Path::$config->webDir . DS . Path::$config->assetJoin;
        
        Path::deleteAllFiles($baseDir . "js/*.js");
        Path::deleteAllFiles($baseDir . "css/*.css");
    }
    public function clearAssetsAction() {
        $this->clearAssetCache();
        $this->fwdDashboard("Asset Cache cleared");
    }
    
    public function clearMenusAction() {
        $this->clearMenuCache();
        $this->fwdDashboard("Menu Cache cleared");
    }
    
    protected function clearHtmlCache()
    {
         $cacheDir = Path::$config->cacheDir;
        
        $this->doEachModule(function ($dmod) use ($cacheDir) {
            $baseDir = $dmod->htmlCache;
            
            if (Path::startsWith($baseDir, $cacheDir)) {
                Path::deleteAllFiles($baseDir . "*.html");
            }
        });
    }
    
    public function clearViewsAction() {
        $this->clearVoltCache();
        $this->clearHtmlCache();
        $this->fwdDashboard("Views Cache cleared");
    }
}
