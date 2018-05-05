<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Page\Controllers;

use Page\Models\Meta;
use Mod\PageInfo as PageInfo;
use Page\Forms\MetaForm;

/**
 * Description of MetaController
 *
 * @author http
 */
class MetaController extends \Phalcon\Mvc\Controller {
    //put your code here
    const Prefix = '/page_admin/meta/';
    
    protected function setView($pick) {
        $view = $this->view;
        $this->ctx->pickView($view, 'meta/' . $pick);
        $view->myController = self::Prefix;
    }
    protected function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
    }
    public function indexAction()
    {
        $this->buildAssets();
        $numberPage = $this->request->getQuery("page", "int");
        
        if (is_null($numberPage))
        {
            $numberPage = 1;
        }
        else {
             $numberPage = intval($numberPage);
        }
            
        $grabsize = 10;
        $start = ($numberPage-1) * $grabsize;
        //SQL_CALC_FOUND_ROWS
        $sql = "select  SQL_CALC_FOUND_ROWS * from meta order by meta_name"
                . " limit " . $start . ", " . $grabsize;

         
        $mm = $this->db;
        $mm->connect();
        
        $stmt = $mm->query($sql);
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);    
        $results = $stmt->fetchAll();
    
        $cquery = $mm->query("SELECT FOUND_ROWS()");
        $cquery->setFetchMode(\Phalcon\Db::FETCH_NUM);
        $maxrows = $cquery->fetch();
        
        $paginator = new PageInfo($numberPage, $grabsize, $results, $maxrows[0]);
        $view = $this->view;
        $acl = $this->acl;

        $vars = [
            'page' => $paginator,
            'user_id' => $acl->userId,
            'isEditor' => $acl->hasRole('Admin'), 
        ];
        $this->setView( 'index');
        $view->setVars($vars);
    }
    
    public function editAction()
    {
        $this->buildAssets();
        if ($this->request->isPost())
        {
            return $this->doPost();
            
        }
        $id = $this->request->getQuery("id", "int");
        if (is_null($id) || $id===0)
        {
            return newAction();
        }
        $meta = Meta::findFirstById($id);
        $view = $this->view;
        $this->setView( 'edit');
        $view->form = new MetaForm($meta,null); 
        $view->metaId = $id;
        
    }
    
    function deleteAction()
    {
        $id = $this->request->getQuery("id", "int");
        if (is_null($id) || $id===0)
        {
            return newAction();
        }
        $meta = Meta::findFirstById($id);
        if ($meta)
        {
            if ($meta->delete())
            {
                $this->flash->success('Record deleted');
                $this->response->redirect('meta/index');
            }
            else {
                $this->flash->error('Failed to delete');
            }
        }
    }
    function doPost()
    {  
        $id = $this->request->getPost('id', 'int');

        if (!is_null($id) && $id > 0)
        {
            $meta = Meta::findFirstById($id);
        }
        else {
             $meta = new Meta();
        }
        
        
        $meta->assign(array(
            'meta_name' => $this->request->getPost('meta_name', 'striptags'),
            'template' => $this->request->getPost('template'),
            'display' => $this->request->getPost('display'),
            'data_limit' => $this->request->getPost('data_limit', 'striptags'),
        ));

        if (is_null($meta->id) || $meta->id===0)
        {
            $meta->id = null;
            if (!$meta->save()) {
                $this->flash->error($meta->getMessages());

            } else {
                $this->flash->success("Meta record was created successfully");
            }

            // get the id?
        }
        else { // exists already
            if (!$meta->save()) {
                $this->flash->error($meta->getMessages());

            } else {
                $this->flash->success("Meta record was updated successfully");
            }                
        }
        //Tag::resetInput();
        $view = $this->view;
        $view->form =  new MetaForm($meta,null);    
        $view->metaId = $id;
    }
    public function newAction()
    {
        $this->buildAssets();
        
        
        $this->setView('new');
        $view = $this->view;
        if ($this->request->isPost()) {
            $this->doPost();
        }
        else {
            $view->form = new MetaForm(null);        
        }
    }
}
