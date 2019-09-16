<?php

namespace Mod\Controllers;

use Phalcon\Mvc\View; 

/**
 * Unlike all other controllers, cannot use \Pcan\Controllers\BaseController
 * because that is probably where the errors are.
 */
class ErrorsController  extends \Phalcon\Mvc\Controller {
    /// Allow view for bare message
    /*
    protected function userAccess()
    {
        $sec = $this->acl;
        if ($sec->hasRole('User'))
        {
            return true;
        }
	else {
    	     if (!$this->config->offsetExists('blockGuest'))
	     {
	     	return true;
	     }
	     return !$this->config->blockGuest;
	}
        $view = $this->view;
        $view->setTemplateBefore('nothing');
        $view->setViewsDir(PCAN_DIR . 'views');
        $view->pick('index/nothing');
        return false;
    }
     * */
     
    protected function userAccess() {
        return true;
    }
    
    public function initialize() {
        
    }
    public function buildAssets()
    {
        
    }
    public function indexAction()
    {
        if (!$this->userAccess())
            return true;
        
        //$this->buildAssets();
        //$this->view->msg = 'URI : ' . $this->ctx->url;
        return $this->setupView();
    }
    private function setupView()
    {
        if (!$this->userAccess())
            return true;
        $this->buildAssets();
        //$this->view->setRenderLevel(
        //    View::LEVEL_ACTION_VIEW
        //);

        $this->view->msg = $this->dispatcher->getParam('msg');
        $viewName = $this->dispatcher->getControllerName() . "/" . $this->dispatcher->getActionName();
        $ctx = $this->di->get('ctx');
        $vdir = $ctx->getActionViewsDir($viewName);
        $this->view->setViewsDir($vdir);
    }
    public function show404Action()
    {
        
        return $this->setupView();
    }
    public function show500Action()
    {
        return $this->setupView();
    }
}