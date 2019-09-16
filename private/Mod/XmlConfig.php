<?php
namespace Mod;

class XmlConfig extends \stdClass {
    const XC_ARRAY = 0;
    const XC_TABLE = 1;
    const XC_CONFIG = 2;
    const XC_VALUE = 3;
    
    static function replaceDefines(XmlConfig $f) {
        $map = get_defined_constants();
        $f->replaceVars($map);
    }
    
    function has($name) {
        return isset($this->$name);
    }
    function replaceVars($map) {
        foreach($this as $key => $value) {
            if (is_string($value)) {
                $matches = null;
                if (preg_match('/\${(\w+)}/', $value, $matches)) {
                    $r = str_replace($matches[0], $map[$matches[1]], $value);
                    $this->$key = $r;
                }
            }
            elseif (is_a($value,'\Phalcon\Config'))
            {
                static::replaceDefines($value);
            }
        }
    }

    static public function fromArray(array $a) {
        $cfg = new XmlConfig();
        return $cfg->addArray($a);
    }
    static public function fromFile(string $filename)
    {
        $xml = new XmlArray();
        return $xml->parseFile($filename);
    }
    public function addFile(string $filename) {
        $xml = new XmlArray($this);
        return $xml->parseFile($filename);
    }
    public function addArray(array $root) {
        foreach($root as $key => $val) {
            $this->$key = $val;
        }
        return $this;
    }
}