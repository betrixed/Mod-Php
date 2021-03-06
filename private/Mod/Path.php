<?php

/**
 * Read any config file and make a Phalcon\Config object.
 * File path OS conversion and directory termination
 *
 * @author Michael Rynn
 */

namespace Mod;

use Phalcon\Config;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

class Path {

    static public $config; // global registry / config
    /**
     * Correct slash direction on path according to preferred OS path.
     * Assume no escaped characters.
     * @param string $path
     * @return string
     */
    
    static function startsWith($target, $with) {
        return (substr($target,0, strlen($with)) === $with);
    }
    static function native(string $path) {
        if (DS == '/') {
            $result = str_replace("\\", DS, $path);
        } else {
            $result = str_replace('/', DS, $path);
        }
        return $result;
    }

    /**
     * 
     * Ensure path ends with OS native dir separator.
     * Does nothing if OS separator already terminates path.
     * Otherwise replaces alternate OS separators and adds terminal separator.
     * @param string $path
     * @return string
     */
    static function endSep(string $path) {
        $sep = substr($path, -1);
        if ($sep !== DS) {
            if ($sep == "\\") {
                // This must be unix using path with windows configuations, or a typo
                $result = self::native($path);
            } else if ($sep == '/') {
                // This may be windows, ok, but visually nice if all point same way
                $result = self::native($path);
            } else {
                $result = self::native($path) . DS;
            }
            return $result;
        }
        return $path;
        
    }
    
    static public function deleteAllFiles($globpath)
    {
       foreach(glob($globpath) as $file) {
            unlink($file); 
        }
    }
    /** 
     * Ensure path does not end with a directory separator character.
     * @param string $path
     * @return string
     */
    
    static function noEndSep(string $path) {
        $sep = substr($path, -1);
        if ($sep == "\\" || $sep == '/') {
            $result = substr($path, 0, strlen($path) - 1);
            return ($sep == DS) ? $result : self::native($result);
        }
        return $path;
    }
    
    /**
     * 
     * @param type $config
     * @param type $path
     */
    
    
    static function replaceKeyValues(&$table)
    {
        foreach($table as $key => $value) {
            if (is_string($value)) {
                $newValue = self::defineReplace($value);
                if (!empty($newValue))
                {
                    $table[$key] = $newValue;
                }
            }
            else if (is_array($value)) {
                static::replaceKeyValues($value);
            }
        }
    }

    static function valuesCallback($config, $valueCallback) {
        if (!is_callable($valueCallback)) { 
            throw new \Exception('Needs function for callback');
        }   
        foreach (get_object_vars($config) as $key => $value) {
            if (self::isMergeable($value)) {
                self::valuesCallback($value, $valueCallback);
            } else {
                $changed = \call_user_func($valueCallback, $value);
                if ($changed) {
                    $config->$key = $value;
                }
            }
        }
    
    }
    /**
     * Return appropriate Phalcon\Config adapter for file path.
     * 
     * @param type $path
     * @return Toml|\Phalcon\Config\Yaml|Ini|\Phalcon\Config\Json|Xml
     * @throws \Exception
     */
    static function getConfig($path)  {
        $pinfo = pathinfo($path);
        $ext = $pinfo['extension'];
        $cachePath = Path::endSep(self::$config->configCache) . 
                        str_replace([DS,':'],'_',$pinfo['dirname']) .
                        '-' . $pinfo['filename'] . '.ktc';
        
        switch ($ext) {
            case 'php':
                $obj = require $path;
                if (is_array($obj)) {
                    $result = XmlConfig::fromArray($obj);
                }
                else if (is_a($obj,'\Mod\XmlConfig')) {
                    $result = $obj;
                }
                break;
            /*case 'ini':
                $result = new Ini($path);
                break;*/
            case 'xml':
                $result = XmlConfig::fromFile($path);
                if (is_array($result)) {
                    $result = XmlConfig::fromArray($result);
                }
                break;
            /*case 'json':
                $result = new Json($path);
                break;
            case 'yaml':
                $result = new Yaml($path);
                break;*/
            default:
                $result = null;
                break;
        }
        if (!empty($result)) {
            XmlConfig::replaceDefines($result);
        }
        return $result;
    }
    static $gDefinePreg;
    static function getDefinePreg() : Preg
    {
        if (empty(Path::$gDefinePreg)) {
            Path::$gDefinePreg = new Preg(1,'\${(\w+)}');
        }
        return Path::$gDefinePreg;
    }
    /**
     * Replace for string $value any ${CONSTANT_NAME} with its defined value.
     * Throw Exception if CONSTANT_NAME value is null
     * @param string $value
     * @return string new value or empty
     * @throws \Exception
     */
    static function defineReplace( $value ) {
        $changed = false;
        if (is_string($value) && strlen($value) > 0) {
            $preg = self::getDefinePreg();
            
            $marray =  $preg->matchAll($value);
                
            if (count($marray)) {
                foreach($marray as $cap) {
                    $cname = $cap[1];
                    $rval = constant($cname);
                    if (is_null($rval)) {
                        throw new \Exception('Constant not defined: ' . $cname);
                    }
                    $changed = true;
                    $value = str_replace($cap[0], $rval, $value);
                }
                if ($changed) {
                    return $value;
                }
            }
        }
        return false;
    }
    
    /** 
     * Return the first matching root path from this.
     * Such as to find a view directory or conficuation file.
     * @param array $viewDirs To find which one to be returned. 
     *         Trailing seperator expected.
     * @param string $pathFile Eg 'path/file'
     * @param array $fileTypes Extensions with dot ['.volt', '.phtml']
     * @return false if no match, else array of [ path, fileType ]
     */
    static function findFirstPath(array $viewDirs, string $pathFile, array $fileTypes) {
        foreach($viewDirs as $vpath) {
            $testPath = $vpath . $pathFile;
            foreach($fileTypes as $extType) {
                if (file_exists($testPath . $extType)) {
                    return [$vpath, $extType];
                }
            }
        }
        return false;
    }
    static function mergeConfigFile($object, $fileName)
    {
        $extraConfig = Path::getConfig($fileName);
        $object->merge($extraConfig);
    }
}
