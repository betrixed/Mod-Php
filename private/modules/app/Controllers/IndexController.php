<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mod\App\Controllers;

/**
 * Description of IndexController
 *
 * @author michael
 */
class IndexController extends \Phalcon\Mvc\Controller
{
    //put your code here
    public function indexAction()
    {
        $view = $this->view;
        $ctx = $this->di->get('ctx');
        $cdir = $ctx->getActionViewsDir('index/index');
        $view->setViewsDir($cdir);
        
    }
}
