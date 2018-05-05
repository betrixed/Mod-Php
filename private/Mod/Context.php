<?php

/**
 * @author Michael Rynn
 */

namespace Mod;

use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\View;

use Phalcon\Mvc\Router;
Use Pun\TomlReader as TomlParser;

use Phalcon\Logger\Adapter\File as Logger;
use Phalcon\Logger\Formatter\Line as LineFormatter;


/**
 * This started out as code to only setup for 1 module for each request.
 * It collects a lot of context, including the active module configuration
 * Registered service as 'ctx' 
 * It is expected to be created early on, so handles exceptions as a plugin, as execution context
 * requires dynamic changes to exception handling..
 * Replaces NotFoundPlugin
 * 
 * Uses a module_data section of first configuration 'config' object
 * and 'defaultModule' property of 'config'
 * Each module needs 'dir', 'namespace', optional 'bootstrap'
 */
class Context implements \Phalcon\Di\InjectionAwareInterface {

    public $controller; // set after dispatch
    public $action;
    public $application; // for pokes
    
    public $config; // global Phalcon/Config
    public $allModules; // array of modules PhalconConfig
    public $modules; // Array of  dynamic configured modules 
    public $activeModule; // config subsection of active module
    public $initialURI;  // first time look at GET['_url']
    public $uriModule; // if a module component in GET{'_url']
    public $isMakingView;  // if true, exceptions need to handled with grace
    public $acl; // security plugin, if any
    /**
     * 
     * @param \Phalcon\DiInterface $di
     */

    public function setDI(\Phalcon\DiInterface $di) {
        $this->di = $di;
    }

    /**
     * 
     * @return \Phalcon\DiInterface
     */
    public function getDI() {
        return $this->di;
    }
    /** 
     * Full file path and extension, switch on extension type
     * to read the configuration as nested array.
     * @param string $full
     * @param string $extension
     * @return array
     */
    static function getArrayConfig(string $full, string $extension) : array {
        switch($extension) {
            case 'toml' :
                $routeData = TomlParser::parseFile($full);
                return $routeData->toArray();
            case 'php' :
                $routeData = require $full;
                return $routeData;
            default:
                $config = Path::getConfig($full);
                return $config->toArray();
        }   
    }
    function getRoutesConfigPath() {
        $modConfig = $this->activeModule;
        $path = $modConfig->dir;
        
        if (isset($modConfig->routes)) {
             $full = $modConfig->routes;
             return $full;
        }
        else {
            $lookFor = "routes";
            $path = Path::endSep($path);
            $files = scandir($path);
            foreach($files as $test) {
                if (substr($test,0,strlen($lookFor))==$lookFor){
                    $full = $path . $test;
                    return $full;
                }
            }
            throw new \Exception("Cannot find a routes.* in " . $path);
        }
        
    }
    function routerService() {
        $modConfig = $this->activeModule;
        $path = $modConfig->dir;
        $routeFile = $this->getRoutesConfigPath();
        $routeCache = $this->config->configCache . "/routes_" . $modConfig->name . ".dat";
        
        if (!file_exists($routeCache) || (filemtime($routeCache) < filemtime($routeFile)) ) {
        //if(true) {
            $info = pathinfo($routeFile);
            $routeData = self::getArrayConfig($routeFile, $info['extension']);
            $router = new Router(false);
           
            $unpack = new RoutesUnpack($router, $modConfig->alias, $modConfig->isDefaultModule);
            $unpack->addRouteData($routeData);
            file_put_contents($routeCache,serialize($router));
        }
        else {
            $router = unserialize(file_get_contents($routeCache));
        }
        // Set default module and URI after unserialize
        if ($modConfig->isDefaultModule) {
               $router->setDefaultModule($modConfig->alias);
               if ($this->uriModule === $modConfig->name)
               {
                   $_GET['_url'] = str_replace(
                           '/' . $this->uriModule, 
                           '',
                           $this->initialURI
                           );
               }
        }
        
        $this->di->setShared('router', $router);
    }
    

    public function getCacheResponse($cache_file, $html_func) {
        $content = $this->getCacheHtml($cache_file, $html_func);
        $response = new \Phalcon\Http\Response();

        
        $response->setContent($content);
        return $response;
    }
    public function getCacheHtml($cache_file, $html_func){
        $frontCache = new \Phalcon\Cache\Frontend\Output(array("lifetime" => 900));// 15 minutes
        $cachedir = $this->activeModule->htmlCache;
        $cache = new \Phalcon\Cache\Backend\File($frontCache, array("cacheDir" => $cachedir));
        $content = $cache->start($cache_file);
        if (empty($content) ){
            
            $content =  $html_func();
            
            echo $content;

            $cache->save();
        }
        return $content;       
     }
     
    function dispatcherService($namespace) {
        $di = $this->di;

        $di->setShared('dispatcher', function () use ($di, $namespace) {
            $hasACL = $di->has('acl');
            $ctx = $di->get('ctx');
            
            // Create an events manager
            $dispatcher = new MvcDispatcher();
            $dispatcher->setDefaultNamespace($namespace);
            
            $eventsManager = new EventsManager();

            // Listen for events produced in the dispatcher using the Security plugin          
            if ($hasACL) {
                $splug = $di->get('acl');
                $ctx->acl = $splug;
                $eventsManager->attach('dispatch:beforeExecuteRoute', $splug);
            }

            // Handle exceptions 
            $eventsManager->attach('dispatch:beforeException', 
            function(Event $event, $dispatcher, \Exception $exception = null) use ($ctx) {
                if (!is_null($exception)) {
                    return $ctx->beforeException($event, $dispatcher, $exception);
                }
                return true;
            });
                
            $dispatcher->setEventsManager($eventsManager);
            return $dispatcher;
        });
    }
    /** useImplicitView was turned off to delay view creation
     *  and it can't be switched back one while in the handle procedure.
     *  
     */
    public function getDispatchView() {
        $viewPath = $this->controller . DIRECTORY_SEPARATOR . $this->action;
        return $this->getActionView($viewPath);
        
    }
    /**
     * Setting the viewsDir property can be delayed until render
     */
    public function getActionViewsDir($controllerAction)
    {
        $mod = $this->activeModule;
        
        // make ordered list of paths to look for $controllerAction
        $viewsDir = $mod->viewsDir;
        if (is_array($viewsDir)) {
            /** For some reason, array of paths can bugger up getting
              content out of render
             This is because all possible matches will get compiled
             and one of them might give errors.
             Therefore, only pass the first match.
             */
            $match = Path::findFirstPath($viewsDir,$controllerAction,['.volt']);
            if (is_array($match)) {
                return $match[0];
            }
            else {
                return $viewsDir[0]; // Instead of last?
            }
        }
        else {
            return $viewsDir;
        }
    }
    /** Get a new view object for current module, 
     *  for given 'controller/action', finding first match
     *  in module views directory array
     * @param type $controllerAction
     */
    public function createView() {
        $view = new View();
        $view->setDI($this->di);
        $view->registerEngines([".volt" => 'volt']);
        return $view;
    }
    /**
     * Select the view using the controller/action lookup
     * @param type $view
     * @param type $controllerAction
     * @throws \Exception
     */
    public function pickView($view, $controllerAction)
    {
        if (!isset($controllerAction))
        {
            throw new \Exception("pickView: controller/action not set");
        }

        $viewsDir = $this->getActionViewsDir($controllerAction);
        $view->setViewsDir($viewsDir);
        $view->pick($controllerAction);
        // View templates can use this to embed in the output
        $view->myDir = $viewsDir . $controllerAction;
        $this->setViewUser($view);
    }
    public function viewService() {
        $di = $this->di;
        
        $ctx = $this;
        $di->set("view", function () use ($ctx) {
            return $ctx->createView();
        }
        );

        $cacheDir = $this->activeModule->voltCache;
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

            return $volt;
        });
    }

    /**
     * @param DiInterface $di
     * @param type $logfile
     * 
     * Set up a 'logger' service, to dump messages in $logfile (full path)
     */
    public function loggerService($logfile) {
        $di = $this->di;
        $di->set("logger", function () use ($logfile, $di) {
            $router = $di->get('router');
            if (!empty($router)) {
                $controller = $router->getControllerName();
                $action = $router->getActionName();
                $formatter = new LineFormatter("[%date%][Controller: " . $controller . "->Action: " . $action . "][%type%]{%message%}");
            } else {
                $formatter = new LineFormatter("[%date%][%type%]{%message%}");
            }
            $logger = new Logger($logfile);
            $logger->setFormatter($formatter);
            return $logger;
        }, false);
    }

    public function debug($controller, $action) {
        echo "$controller, $action <br>" . PHP_EOL;
    }

    public function setViewUser($view) {
        if (!empty($this->acl)) {
            $sec = $this->acl;
            $view->userId = $sec->userId;
            $view->userName = $sec->userName;
            $view->roleList = $sec->getRoles(); 
            
            $view->isUser = $sec->hasRole('User');
            $view->isAdmin = $sec->hasRole('Admin');
            $view->isEditor = $sec->hasRole('Editor');
                 
            $view->isMobile = $sec->isMobile();
            $view->myLogo = $this->config->pcan->logo;
            
            /*
            $uriPath = "/";
            if (!empty($sec->urlModule)) {
                $uriPath .= $sec->urlModule . "/";
            }
            $uriPath .= $sec->controller  . "/";
            
            $view->myController = $uriPath;
             * 
             */
        }
        
    }
    public function setMakingView($bval) {
        $this->isMakingView = $bval;
    }

    public function getName() {
        return $this->activeModule->name;
    }

    private function getDefaultModulesDir() {
        $config = $this->config;
        $defaultDir = $config->exists('modulesDir') ? $config->modulesDir : PHP_DIR . '/modules';
        return Path::noEndSep($defaultDir);
   
    }
    private function dynamicConfig($mod, $name) {
        $mod->name = $name;
        if (!isset($mod->namespace)) {
            throw new \Exception('module_data.' . $name . ' needs namespace value');
        }
        
        if (!isset($mod->dir)) {
            $mod->dir = $this->getDefaultModulesDir() . DS . $name;
        }
        else {
            $mod->dir = Path::noEndSep($mod->dir);
        }
        
        if (!$mod->exists('path')) {
            $mod->path = $mod->dir . '/Module.php';
        }
         
        if (!$mod->exists('className')) {
            $mod->className = $mod->namespace . '\Module';
        }
        
        if (!isset($mod->enabled)) {
            $mod->enabled = true;
        }
        $cacheDir = Path::noEndSep($this->config->cacheDir);
        // view service configuation gets paths with separator ends
        $mod->voltCache = $cacheDir . DS . 'volt_' . $name . DS;
        $mod->htmlCache = $cacheDir . DS . 'html_' . $name . DS;
        $mod->viewsDir = $mod->dir . DS . 'views' . DS;
    }

    /**
     * Return registerModules array for the active module
     * This should not be called if Module already registered.
     * 
     * Default for path is {dir}/Module.php
     * Default for className is {namespace}\Module
     */
    public function getRegisterArray() {
        $mod = $this->activeModule;

        return [$mod->alias => [
                'path' => $mod->path,
                'className' => $mod->className
        ]];
    }

    /**
     * Return path to next bootstrap file or Exception
     * Relative path means in the Module directory tree.
     * @param type $modConfig
     * @return string
     */
    protected function setActiveModule($modConfig) {
        $this->activeModule = $modConfig;
        $bootstrap = $modConfig->get('bootstrap', 'mod_bootstrap.php');
        $isAbsolute = Path::startsWith($bootstrap,ROOT_DIR);
        if (!$isAbsolute) 
        {
            $bootstrap = $modConfig->dir . DS . $bootstrap;
        }
        
        if (file_exists($bootstrap)) {
             return $bootstrap;
        }
        else {
            throw new \Exception("File not found: " .  $bootstrap);
        }
       
    }

    static public function uriModuleStr($myURI)
    {
        if (!empty($myURI) && $myURI !== '/') { // find match module name
            $ipos = strpos($myURI, '/');
            if ($ipos == 0) {
                $ipos = strpos($myURI, '/', 1);
                return ($ipos > 1) ? substr($myURI, 1, $ipos - 1) : substr($myURI, 1);
            }
        }  
        return '';
    }


    /**
     * This action is executed before execute any action in the application
     * Needs to be installed in dispatcher service, using 'ctx' service to get it.
     * @param Event $event
     * @param MvcDispatcher $dispatcher
     * @param Exception $exception
     * @return boolean
     */
    public function beforeException(Event $event, MvcDispatcher $dispatcher, \Exception $exception) {
        $di = $this->di;

        $msg = "URI: " . $di->get('router')->getRewriteUri() . PHP_EOL;

        $msg .= $exception->getMessage() . PHP_EOL . $exception->getTraceAsString();
        if ($this->config->logErrors) {
            error_log($msg, 3, $this->config->errorLog);
        }

        if (!$this->isMakingView) {
            $keyParams = ['msg' => $msg];

            $dispatcher->setParams($keyParams);
            $dispatcher->setNamespaceName("Mod\\Controllers");

            if ($exception instanceof DispatcherException) {
                switch ($exception->getCode()) {
                    case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                    case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                        $this->isMakingView = true;
                        $dispatcher->forward(
                                ['controller' => 'errors',
                                    'action' => 'show404']
                        );
                        return false;
                }
            }

            $dispatcher->forward(
                    ['controller' => 'errors',
                        'action' => 'show500']
            );

            return false;
        }
        return true;
    }

    /**
     * Setup a default dependency injector with all shared services
     */
    public function initServices() {
        $di = new FactoryDefault();
        $this->setDI($di);

        $di->setShared('config', $this->config);
        $di->setShared('ctx', $this);

        
        if ($this->config->logErrors) {
            $this->loggerService($this->config->errorLog);
        }

        return $di;
    }

    /**
     * The modules array values are a mix of string alias, and configuration arrays.
     * Follow alias name if necessary. Return null if not found.
     * name can be a single level alias to real module configuration key
      allow for 'alias' for multiple uri's used by a module, only 1 level of indirection allowed
     * @param String $alias
     */
    public function getModuleConfig($alias) {
        if (isset($this->modules[$alias])) {
            return $this->modules[$alias];
        }
        if (isset($this->allModules[$alias])) {
            $myConfig = $this->allModules[$alias]; // a string or Phalcon\Config
            if (is_string($myConfig) && isset($this->allModules[$myConfig])) {
                $realName = $myConfig;
                $myConfig = $this->allModules[$myConfig];
            } else {
                $realName = $alias;
            }
            if (!($myConfig instanceof \Mergeable) && !($myConfig instanceof \Phalcon\Config)) {
                throw new \Exception("Module configuration not found: $alias");
            }
            $this->dynamicConfig($myConfig, $realName);
            $this->modules[$alias] = $myConfig;
            $myConfig->alias = $alias;
            $myConfig->isDefaultModule = ($alias == $this->config->defaultModule) ? true : false;
            return $myConfig;
        }
        return null;
    }
    
    public function init($config) {
        $this->config = $config;
        date_default_timezone_set($config->timezone);
        
        $config->phpVersion = phpversion();
        $config->phalconVersion = phpversion('phalcon');
        
        $di = $this->initServices(); // setup di, and shared
        // Share the modules config
        $di->setShared('modules', $config->module_data);
         //
        // Phalcon\Config objects are sort of iterable  
        $this->allModules = [];
        $module_data = $config->module_data;
        foreach ($module_data as $modName => $moduleConfig) {
            $this->allModules[$modName] = $moduleConfig;
            if (isset($moduleConfig['services'])) {
                // * must * have a Module.php containing 
                //  static function {service}( $di )
                // config with default name
                $smod = $this->getModuleConfig($modName);
                // activate its namespace
               (new \Phalcon\Loader())->registerNamespaces([
                     $smod->namespace => $smod->dir
                ])->register();
               
               // make a new instance of Module
                $module = new  $smod->className();
                $module->registerAutoloaders($di);
                $module->registerServices($di);
            
            }
        }

        $this->initialURI = filter_input(INPUT_GET, '_url');
        $key = self::uriModuleStr($this->initialURI);
        if (!empty($key)) {
            if ('/' . $key === $this->initialURI) {
        // see if the URL is a mapped keyword in config['urlmap']
                 $config = $this->config;
                 if ($config->exists('urlmap')) {
                     $config = $config['urlmap'];
                     if ($config->exists($key)) {
                         $item = $config[$key];
                         $url = '/' . $item->controller . '/' . $item->action;
                         $_GET['_url'] = $url;
                         $key = self::uriModuleStr($url);
                     }
                 }
             }          
        }
        $this->uriModule = $key;
        $alias = (!empty($key) && isset($this->allModules[$key])) 
                ? $key
                : $config->defaultModule;
        $myConfig = $this->getModuleConfig($alias);
        // nearly good to go
        return $this->setActiveModule($myConfig);
    }

    function dispatch(string $namespace, $registration = null) {
        
        $this->dispatcherService($namespace);
        $this->routerService();

        // register callback on this instance
        if (empty($registration)) {
            $registration = $this->getRegisterArray();
        }
        $app = new \Phalcon\Mvc\Application($this->di);
        $app->registerModules($registration);
        // delay view creation and buffering
        //$app->useImplicitView(false); 
        $this->application = $app;
        
        $response = $app->handle();
        $response->send();
    }
    
    public function getExplicitResponse($params)
    {
        $viewPick = $params['controller'] . '/' .  $params['action'];
        
        $view = $this->makeExplicitView($viewPick);
        $view->setLayoutsDir('layouts/');
        $view->setRenderLevel(View::LEVEL_MAIN_LAYOUT);
        $view->start();
        $view->setVars($params);

        $view->render($params['controller'], $params['action']);
        $content = ob_get_contents();
        //ob_end_clean();
        $view->finish();

        return $content;
        
    }
    
    public function makeExplicitView($controllerAction)
    {
        $view = $this->createView();
        $viewsDir = $this->getActionViewsDir($controllerAction);
        $view->setViewsDir($viewsDir);
        $view->myDir = $viewsDir . $controllerAction;
        $this->setViewUser($view);
        return $view;
    }
}
