<?php

/*
See the "licence.txt" file at the root "private" folder of this site
*/
namespace Mod\Plugins\Menu;

class Menu {
    public $id;
    public $parent;
    public $submenu;
    public $caption;
    public $action;
    public $controller;
    public $restrict;  
    public $serial;
    public $class;
    
    protected $isSorted = false;
    
    public function childCount()
    {
        return is_array($this->submenu) ? count($this->submenu) : 0;
    }
    public function addItem($item)
    {
        $this->isSorted = false;
        $this->submenu[] = $item;
    }
    static function menuCmp($a,$b)
    {
        return $a->serial - $b->serial;
    }
    public function getSubmenus()
    {
        if (!$this->isSorted)
        {
            usort($this->submenu, array('Pcan\Plugins\Menu\Menu','menuCmp'));
            $this->isSorted = true;
        }
        return $this->submenu;
    }

}
