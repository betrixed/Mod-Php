<?php

namespace Mod\Plugins;

use Mod\Plugins\Menu\Menu;
use Mod\Plugins\MenuTree;
use Phalcon\Mvc\User\Component;
use Mod\Path;

/**
 * Elements
 *
 *  UI elements for the application.
 *  Asset output convenience.
 */
class Elements extends Component {

    private $secId;
    //private $mainMenu;
    private $assetList;
    private $loggedIn;
    
    private $assetSrc;
    private $assetProd;
    private $web;
    private $config;
    
    public function __construct()
    {
        $this->config = $this->di->get('config');
        $this->web = Path::endSep($this->config->webDir);
        
        $this->assetSrc = "/assets/";
        $this->assetProd = "file/";
    }
    
    static function dropDownSub($name) {
        echo PHP_EOL . '<li class="dropdown-submenu">',
        '<a href="#" class="dropdown-toggle" data-toggle="dropdown">',
        $name, '</a>',
        PHP_EOL . '<ul class="dropdown-menu">';
    }

    static function dropDownStart($name) {
        echo PHP_EOL . '<li class="dropdown">',
        '<a href="#" class="dropdown-toggle" data-toggle="dropdown">',
        $name, '<b class="caret"></b></a>',
        PHP_EOL . '<ul class="dropdown-menu multi-level">';
    }

    static function dropDownEnd() {
        echo '</ul></li>';
    }

    static function outputClass(array $class) {
        $test = false;
        if (count($class) > 0) {
            echo ' class="';
            foreach ($class as $item) {
                if ($test) {
                    echo ' ';
                } else {
                    $test = true;
                }
                echo $item;
            }
            echo '"';
        }
    }

    private function echoAction($menu, $isActive = false) {
        echo PHP_EOL . '<li';

        if (isset($menu->class) || $isActive) {
            $class = [];
            if (isset($menu->class)) {
                $class[] = $menu->class;
            }
            if ($isActive) {
                $class[] = 'active';
            }
            $this::outputClass($class);
        }
        echo ">";
        $link = (!empty($menu->controller)) ? $menu->controller : '';
        if (!empty($menu->action)) {
            if (strlen($link) > 0)
            {
                $link .= '/';
            }
            $link = '/' . $link . $menu->action;
        }
        if (strlen($link) > 0) {
            echo $this->tag->linkTo($link, $menu->caption);
        } else {
            echo $menu->caption;
        }
        echo '</li>';
    }

    /**
     * 
     * @param type $menu - submenu to process
     */
    public function processSubMenu(array $menuList, $wasDrop) {
        foreach ($menuList as $menu) {
            if (!empty($menu->restrict)) {
                // must have role to see
                if (!in_array($menu->restrict, $this->secId->roleList)) {
                    continue;
                }
            }
            if ($menu->childCount() > 0) {
                if ($wasDrop) {
                    $this::dropDownStart($menu->caption);
                } else {
                    $this::dropDownSub($menu->caption);
                }
                $this->processSubMenu($menu->getSubmenus(), false);
                if ($wasDrop) {
                    $this::dropDownEnd();
                } else {
                    $this::dropDownEnd();
                }
            } else {
                $this->echoAction($menu);
            }
        }
    }

    public function getLoginMenu() {
        $rightMenu = new Menu();
        $menu = new Menu();
        
        $menu->controller = 'secure/id';
        $rightMenu->addItem($menu);
        $rightMenu->class = 'navbar-right';
        
        $urlmap = $this->config->urlmap;
        
        if ($this->loggedIn) {
            $item = $urlmap['logout'];
            
            
            
        } else {
            $item = $urlmap['login'];
        }
        $menu->action = $item->action;
        $menu->controller = $item->controller;
        $menu->caption = $item->caption;
        $menu->class = $item->class;
        
        return $rightMenu;
    }

    /**
     * Builds header menu with left and right items
     *
     * @return string
     */
    public function getMenu($menuName, $login = false) {
        $this->secId = $this->acl;
        $this->loggedIn = ($this->secId->userId) ? True : False;
        $this->mainMenu = new MenuTree();
        $leftMenu = $this->mainMenu->getMainMenu($menuName);
        $theMenu = [$leftMenu];

        if ($login || $this->loggedIn) {
            $rightMenu = $this->getLoginMenu();

            $theMenu[] = $rightMenu;
        }
        $controllerName = $this->view->getControllerName();

        // Menu is nested associative array
        foreach ($theMenu as $data) {
            echo PHP_EOL . '<div class="nav-collapse">',
            '<ul class="nav navbar-nav ', $data->class, '">';


            if (isset($data->restrict)) {
                // must have role to see
                if (!in_array($data->restrict, $this->secId->roleList)) {
                    continue;
                }
            }
            if ($data->childCount() > 0) {
                $this->processSubMenu($data->getSubmenus(), true);
            } else {
                if (isset($data->controller)) {
                    $isActive = ($controllerName == $data->controller) ? true : false;
                    $this->echoAction($data, $isActive);
                }
            }

            echo PHP_EOL . '</ul>', '</div>';
        }
    }

    /**
     * Returns menu tabs
     */
    public function getFBook() {
        $user = $this->acl;
        echo PHP_EOL . '<script id="fbapp" type="application/xml">';
        if (is_array($user->fbook)) {
            echo PHP_EOL . '<fbapp>ok</fbapp>';
        } else {
            echo PHP_EOL . '<fbapp>nak</fbapp>';
        }
        echo PHP_EOL . '</script>';
    }

    /* // Output array of tabs
     * // 'Caption' => [ controller, action, any ] 
     * public function getTabs($tabs) {
      $controllerName = $this->view->getControllerName();
      $actionName = $this->view->getActionName();
      echo '<ul class="nav nav-tabs">';
      foreach ($tabs as $caption => $option) {
      if ($option['controller'] == $controllerName && ($option['action'] == $actionName || $option['any'])) {
      echo '<li class="active">';
      } else {
      echo '<li>';
      }
      echo $this->tag->linkTo($option['controller'] . '/' . $option['action'], $caption), '</li>';
      }
      echo '</ul>';
      }
     * 
     */

    /**
     * Test for properties of view to insert in <head> section of HTML
     * So far its an array of HTML strings for meta-data
     * and a seperate property for the canonical link
     */
    public function getHeaders() {
        $view = $this->view;
        if (isset($view->metaloaf) && (count($view->metaloaf) > 0)) {
            foreach ($view->metaloaf as $mtag) {
                echo $mtag . PHP_EOL;
            }
        }
        if (isset($view->canonical)) {
            echo "<link rel='canonical' href='$view->canonical' />" . PHP_EOL;
        }
    }

    /**
     * Output registered assets
     */
    public function getAssets() {
        $this->allAssets();
    }

    public function outputStats() {
        echo "PHP " . $this->config->pcan->php;
        echo ", &nbsp; Phalcon " . $this->config->pcan->phalcon;
        echo ", &nbsp; Response time " . sprintf('%.2f ms', (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
        echo ", &nbsp; Memory " . sprintf('%.2f MiB', memory_get_peak_usage() / 1024 / 1024);
    }

    public function allAssets() {
        if (!is_array($this->assetList))
            return;
        $ctx = $this->getDI()->get('ctx');
        $ctx->setMakingView(true);
        try {
            $assets = $this->assets;
            foreach ($this->assetList as $name => $type) {
                $asset = $assets->get($name);
                if ($type == "css") {
                    $assets->outputCss($name);
                } else {
                    $assets->outputJs($name);
                }
            }
        } catch (Exception $ex) {
            
            echo "<!--" . $ex->getMessage() . "-->";
        }
        $ctx->setMakingView(false);
    }

    public function outputAsset($name) {
        if (is_array($this->assetList) || array_key_exists($name, $this->assetList)) {
            $assets = $this->assets;
            $type = $this->assetList[$name];
            if ($type == "css") {
                $assets->outputCss($name);
            } else {
                $assets->outputJs($name);
            }
        }
    }

    public function hasAsset($name) {
        return (is_array($this->assetList) || array_key_exists($name, $this->assetList));
    }

    public function registerAsset($name, $type) {
        $this->assetList[$name] = $type;
    }
    
    protected function addCss_assets($className, $list, $prefix = "")
    {
        $name = $className . 'CSS';
        $coll = $this->assets->collection($name);
        foreach($list as $item)
               $coll->addCss($prefix . $item);
        $this->registerAsset($name,'css'); 
        return $coll;
    }    
    protected function addJs_assets($className, $list, $prefix = "")
    {
        $name = $className . 'JS';
        $coll = $this->assets->collection($name);
        foreach($list as $item)
               $coll->addJs($prefix . $item);
        $this->registerAsset($name,'js');
        return $coll;
    }
    
    public function jsAssetsArray($assets,$prefix)
    {
        if (array_key_exists('js', $assets))
        {
            $list = $assets['js'];
            if (count($list) > 0)
            {
                $name = $assets['name'];
                return $this->addJs_assets($name, $list, $prefix);
            }
        }    
    }
    public function cssAssetsArray($assets,$prefix)
    {  
        if (array_key_exists('css', $assets))
        {
            $list = $assets['css'];
            if (count($list) > 0)
            {
                $name = $assets['name'];
                return $this->addCss_assets($name, $list, $prefix);
            }
        }    
    }
    
    public function addAsset($name) {
        $cfg = $this->di->get('config');
        $path = $cfg->configDir . "/assets/" . $name . ".php";
        if (file_exists($path)) {
            $data = require $path;
            $this->addAssetArray($data);
        }
    }
    
    public function moduleCssList($cssList, $modName) {
        $cssFileName = $modName . ".css";
        if (count($cssList) > 0)
        {
            $prefix = 'css/';

            $assets = ['targetCss' => $cssFileName, 'name' => $modName];

            $list = [];
            foreach($cssList as $cssFile)
            {
                $list[] = $prefix . $cssFile;
            }
            $assets['css'] = $list;
            $this->addAssetArray($assets, "assets/module/");
        }
    }
    
    public function addAssetArray($assets, $assetSrc = null)
    {
        if (is_null($assetSrc))
        {
            $assetSrc = $this->assetSrc;
        }
        if (array_key_exists('targetJs', $assets))
        {
            $targetFile = $assets['targetJs'];
            
            $prodDir = $this->assetProd . 'js/';
            $target = $this->web . $prodDir . $targetFile;
            $uri = '/' . $prodDir . $targetFile;
            
            if (\file_exists($target))
            {
                $this->addJs_assets($assets['name'], [$uri]);
            }
            else {
                $coll = $this->jsAssetsArray($assets, $assetSrc);
                $coll->setTargetPath($target);
                $coll->setTargetUri($uri);
                $coll->join(true);
                $coll->addFilter(new \Phalcon\Assets\Filters\Jsmin());
            }
        }
        else if (array_key_exists('js', $assets))
        {
            $this->jsAssetsArray($assets,$assetSrc);
        }
        if (array_key_exists('targetCss', $assets))
        {
            $targetFile = $assets['targetCss'];
            $prodDir = $this->assetProd . 'css/';
            $target = $this->web . $prodDir . $targetFile;
            $uri = '/' . $prodDir . $targetFile;
            
            if (\file_exists($target))
            {
                $this->addCss_assets($assets['name'], [$uri]);
            }
            else {
                $coll = $this->cssAssetsArray($assets, $assetSrc);
                $coll->setTargetPath($target);
                $coll->setTargetUri($uri);
                $coll->join(true);
                $coll->addFilter(new \Phalcon\Assets\Filters\Cssmin());                
            }
        }
        else if (array_key_exists('css', $assets))
        {
            $this->cssAssetsArray($assets,$assetSrc);
        }
    }
}
