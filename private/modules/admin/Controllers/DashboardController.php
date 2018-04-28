<?php

/**
 * @author Michael Rynn
 */

namespace Admin\Controllers;

class DashboardController  extends \Phalcon\Mvc\Controller {
    public function indexAction()
    {
        $ctx = $this->di->get('ctx');
        $view = $this->view;
        $view->lastLogin = $this->session->get('last');
        $view->chimpEnabled = false;
        $ctx->pickView($view, 'dashboard/index');
        $this->elements->addAsset('bootstrap');
        
        
    }
}