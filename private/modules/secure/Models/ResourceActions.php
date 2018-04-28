<?php

namespace Secure\Models;


class ResourceActions {

    public $name;
    public $actions;
    function __construct($aName)
    {
        $this->name = $aName;
        $this->actions = [];
    }
    
    public function addAction($actName,$id)
    {
        $this->actions[] = [$actName,$id];
    }
};
