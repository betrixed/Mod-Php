<?php

namespace Mod;

use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group as RouterGroup;

/**
 * Michael Rynn
 * Unpack a dense php array format for a modules routes into \Phalcon\Mvc\Router\
 * 
 * 
  /**
 * Add controller alias keyed array of actions using a Router\Group. 
 * Specified with a condensed style of array representation.
 * This is an encoding that tries to minimise the number of characters.
 * The handling of '/' in the pattern fragment is counter-intuitive.
 * A leading '/' is prefixed to all none-empty patterns. 
 * So they are never specified as starting with a '/',  unless the pattern is only '/' itself,
 * in which case the action pattern is empty! 
  ```
  // The URL becomes /{module-alias}    Router uses /index/index
  '/' => [
  'controller' => 'index',
  'actions' => [
  'index' => ['/', ['GET']]
  ]
  ],
  ```
 * An empty action pattern '' implies the default. In this case the action name is also the URL pattern.
  ```
  'generate' => ['', ['GET', 'POST']], // the pattern '' means use 'generate' as the pattern
  ```
 * For instance this example from Module implementation of modtools.
  ```
  // 'index is for the method 'indexAction' of  class SystemsInfoController,
  // prodcuces router URL pattern as /{module}/{controller-alias}, with no terminating / or /action-pattern
  'info' => [
  'controller' => 'system_info',
  'actions' =>[
  'index' => ['/',['GET']]
  ]
  ],
  ```
 * Arguments passed to ModTools\RoutesUnpack
 * '' in pattern means  same as action name.
 * A '/' means empty pattern, otherwise pattern part 
 * will be prefixed with a '/' when router is setup.
 * a '/' .
 * 
 * First level -  controller-alias
 * Second level 
 *          'action' => $pattern
 *          'actions' array of $action => $pattern
 *                      or $action => [ $pattern, optional ($methods, optional($params)) ]
 */
class RoutesUnpack {

    private $moduleName;
    private $isDefaultModule;
    private $router;

    public function __construct($router, $moduleName, $isDefaultModule = false) {
        $this->router = $router;
        $this->moduleName = $moduleName;
        $this->isDefaultModule = $isDefaultModule;
        $this->router->setDefaultModule($moduleName);
    }

    static private function checkPattern($item) {
        if (is_string($item) && (strlen($item) > 0)) {
            return substr($item, 0, 1) == "/" ? $item : "/" . $item;
        } else
            return $item;
    }

    private function addMethodPattern($prefix, $controller, $actionList) {

        foreach ($actionList as $key => $item) {
            $paths = ['controller' => $controller];
            if (is_integer($item)) {
                $pattern = $prefix . self::checkPattern($key);
                if (substr($key, 0, 1) == ':') {
                    $key = substr($key, 1);
                }
                $paths[$key] = $item;
                $this->router->add($pattern, $paths, null);
            } else if (is_string($key)) {
                if (empty($item)) {
                    $item = $key;
                }
                if (!is_string($item)) {
                    throw new \Exception('Value is not a string');
                }
                $paths['action'] = $key;
                $pattern = $prefix . self::checkPattern($item);
                $this->router->add($pattern, $paths, null);
            } else if (is_array($item)) {
                $pattern = $item[0];
                $paths['action'] = $key;
                if (empty($pattern)) {
                    $pattern = '/' . $key;
                } else if ($pattern == '/') {
                    $pattern = '';
                } else {
                    $pattern = '/' . $pattern;
                }
                $methods = count($item) > 1 ? $item[1] : null;
                if ((count($item) > 2) && is_array($item[2])) {
                    $paths = array_merge($paths, $item[2]);
                }
                $this->router->add($prefix . $pattern, $paths, $methods);
            }
        }
    }

    /** action list is numeric keyed list of action names for default 
      String value 'action' for 'actionName' => 'actionMethod',
      Array value ['action',['GET',PUT']] for allowed methods
     */
    private function addSimpleGroupList($group, $actionList) {
        foreach ($actionList as $idx => $item) {
            if (is_array($item)) {
                $actionName = $item[0];
                $methods = $item[1];
                $paths = ['action' => $actionName];
                $group->add('/' . $actionName, $paths, $methods);
            } else {
                $group->add('/' . $item, ['action' => $item], null);
            }
        }
    }

    /**
     * Handle array of 'actions'
     * @param \Phalcon\Mvc\Router\Group $group
     * @param mixed  $data - items are pattern or [pattern, methods[] ] 
     */
    private function addGroup($group, $data) {
        foreach ($data as $action => $pat2) {
            $methods = null;
            $paths = ['action' => $action];
            if (empty($pat2)) {
                $pattern = '/' . $action;
            } else if (is_string($pat2)) {
                if ($pat2 == '/') {
                    $pattern = '';
                } else {
                    $pattern = '/' . $pat2;
                }
            } else if (is_array($pat2) && count($pat2) > 0) {
                $pattern = $pat2[0];
                if (empty($pattern)) {
                    $pattern = '/' . $action;
                } else if ($pattern == '/') {
                    $pattern = '';
                } else {
                    $pattern = '/' . $pattern;
                }
                $methods = count($pat2) > 1 ? $pat2[1] : null;
                if ((count($pat2) > 2) && is_array($pat2[2])) {
                    $paths = array_merge($paths, $pat2[2]);
                }
            }
            $group->add($pattern, $paths, $methods);
        }
    }

    private function addControllerGroup($group, $data) {
        foreach($data as $action => $attr) {
            if (is_array($attr)) {
                $paths = ['action' => $action];  
                if (isset($attr['pattern'])) {
                    $pattern = self::actionPattern($attr['pattern'],$action);
                }
                else {
                    $pattern = self::actionPattern($rkey,$action);
                }
                if (isset($attr['methods'])) {
                    $methods = $attr['methods'];
                }
                else {
                    $methods = null;
                }
                if (isset($attr['params'])) {
                   foreach($attr['params'] as $name => $pidx) {
                       $paths[$name] = $pidx;
                   }
                }
            }
            $group->add($pattern, $paths, $methods);       
        }
    }
 
    /**
     * Handle simple 'action' data.
     * @param string $prefix
     * @param string $controller
     * @param mixed $data
     */
    private function addAction($prefix, $controller, $data) {
        if ($controller == '/') {
            $paths = [];
        } else {
            $paths = ['controller' => $controller];
        }
        if (is_array($data) && count($data) > 0) {
            $paths['action'] = $data[0];
            $pattern = $prefix . '/' . $data[0];
            $methods = count($data) > 1 ? $data[1] : null;
            if (count($data > 2) && is_array($data[2])) {
                $paths = array_merge($paths, $data[2]);
            }
            $this->router->add($pattern, $paths, $methods);
        } else if (is_string($data)) {
            $paths['action'] = $data;
            $this->router->add($prefix . '/' . $data, $paths);
        }
    }
    //return altered value of $pattern, depending on action
    static public function actionPattern($pattern, $action) : string
    {
        if (empty($pattern)) {
            return   '/' . $action;
        } else if ($pattern == '/') {
            return '';
        } else {
            return '/' . $pattern;
        }    
    }
    
    public function patternTable($patterns) {
        $modulePrefix = '/' . $this->moduleName;
        $lenPrefix = strlen($modulePrefix);
        foreach($patterns as $idx => $table) {
            $match = '';
            $action = '';
            $controller = '';
            $other = [];
            $methods = null;
            $paths = [];
            
            
            foreach($table as $key => $value) {
                switch($key) {
                case 'paths':
                    $paths = $value;
                    break;
                case 'match':
                    $match = $value;
                    if (Path::startsWith($match , $modulePrefix)) {
                        $match = substr($match, $lenPrefix);
                    }
                    break;
                case 'controller':
                    $paths['controller'] = $value;
                    break;
                case 'action':
                    if (is_numeric($value)) {
                        $paths['action'] = intval($value);
                    }
                    else {
                        $paths['action'] = $value;
                    }
                    break;
                case 'methods':
                    $methods = $value->toArray();
                    break;
                case 'module':
                    $paths['module'] = $value;
                    break;
                default:
                    if (substr($key,0,1)==':') {
                        $other[substr($key,1)] = $value;
                    }
                    break;
                }
            }
            
            if (empty($match)) {
                throw new \Exception('Route "match" cannot be empty');
            }   
            $paths = array_merge($paths, $other);
            $this->router->add($match, $paths, $methods);
        }
    }
    public function addRouteData($routeData) {
        $defaultController = 'index';
        $defaultAction = 'index';

        // handle 'table' names
        foreach ($routeData as $keyName => $rvalues) {
// does prefix include module name?
            $firstChar = substr($keyName, 0, 1);
            if ($firstChar == '$' || $firstChar == '_') {
                $special = substr($keyName, 1);
                switch ($special) {
                    case 'default' :
                        if (isset($rvalues['controller'])) {
                            $defaultController = $rvalues['controller'];
                        }
                        if (isset($rvalues['action'])) {
                            $defaultAction = $rvalues['action'];
                        }
                        break;
                    case 'notFound' :
                        $controller = isset($rvalues['controller']) ? $rvalues['controller'] : $defaultController;
                        $action = isset($rvalues['action']) ? $rvalues['action'] : $defaultAction;
                        $this->router->notFound(['controller' => $controller, 'action' => $action]);
                        break;
                    case 'pattern' :
                        $this->patternTable($rvalues);
                        break;
                    default:
                        break;
                }
                continue;
            }
            // Table name is a controller name alias

            if ($keyName == '/') {
                $prefixName = '';
                $controller = $defaultController;
            } else {
                $controller = isset($rvalues['controller']) ? $rvalues['controller'] : $keyName;
                $prefixName = (substr($keyName, 0, 1) != '/') ? '/' . $keyName : $keyName;
            }
            $prefix = $this->isDefaultModule ? $prefixName : '/' . $this->moduleName . $prefixName;
            // check for special keys

            if (is_array($rvalues)) {
                if (count($rvalues) == 0) {
                    $this->router->add($prefix . '/' . $defaultAction, ['controller' => $controller,
                        'action' => $defaultAction
                    ]);
                    continue;
                }
                
                if (isset($rvalues['group']) && $rvalues['group']===true) {
                    $routes = new RouterGroup(
                            ['module' => $this->moduleName,
                             'controller' => $controller]);
                    $routes->setPrefix($prefix);
                    $this->addControllerGroup($routes, $rvalues);
                    $this->router->mount($routes);
                    continue;
                }
                foreach ($rvalues as $rkey => $value) {
                    if ($rkey == '_actions') {
                        $this->addMethodPattern($prefix, $controller, $value);
                    } else if ($rkey == '_list') {
                        if (count($value) > 0) {
                            $routes = new RouterGroup([
                                'module' => $this->moduleName,
                                'controller' => $controller]);
                            $routes->setPrefix($prefix);
                            $this->addSimpleGroupList($routes, $value);
                            $this->router->mount($routes);
                        }
                    } else if ($rkey == '_map') {
                        $this->addMethodPattern($prefix, $controller, $value);
                    }
                    else if (is_array($value)) {
                        $paths = ['controller' => $controller, 'action' => $rkey];
                        if (isset($value['pattern'])) {
                            $pattern = self::actionPattern($value['pattern'],$rkey);
                        }
                        else {
                            $pattern = self::actionPattern($rkey,$rkey);
                        }

                        if (isset($value['methods'])) {
                            $methods = $value['methods'];
                        }
                        else {
                            $methods = null;
                        }
                        if (isset($value['params'])) {
                            foreach($value['params'] as $name => $pidx) {
                                $paths[$name] = $pidx;
                            }
                        }
                        $this->router->add($prefix . $pattern, $paths, $methods);
                    }
                    else {
                        $this->addMethodPattern($prefix, $controller, $value);
                    }
                }
            }
        }
        $this->router->removeExtraSlashes(true);
        $this->router->setDefaultController($defaultController);
        $this->router->setDefaultAction($defaultAction);
        $prefix = $this->isDefaultModule ? '' : '/' . $this->moduleName;
        $this->router->add($prefix, ['controller' => $defaultController, 'action' => $defaultAction]);
        $this->router->add($prefix . '/', ['controller' => $defaultController, 'action' => $defaultAction]);
    }

}
