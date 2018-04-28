<?php

namespace Secure\Controllers;

use Phalcon\Logger;
use Phalcon\DI;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\View;
use Phalcon\Paginator\Adapter\Model as Paginator;

use Secure\Forms\ResourceForm;
use Secure\Models\Resource;
use Mod\PageInfo;

class ResourceController extends \Phalcon\Mvc\Controller {
    
    protected function buildAssets()
    {
        $this->elements->addAsset('bootstrap');
    }
    
    
    protected function listPageNum($numberPage, $pageRows, $orderby) {
        $start = ($numberPage - 1) * $pageRows;
        //SQL_CALC_FOUND_ROWS
        $sql = "select SQL_CALC_FOUND_ROWS b.* "
                . " from resource b"
                . " order by " . $orderby
                . " limit " . $start . ", " . $pageRows;

        $db = $this->db;
        $stmt = $db->query($sql);

        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $stmt->fetchAll();

        $cquery = $db->query("SELECT FOUND_ROWS()");
        $cquery->setFetchMode(\Phalcon\Db::FETCH_NUM);
        $maxrows = $cquery->fetch();

        return new PageInfo($numberPage, $pageRows, $results, $maxrows[0]);
    }

    public function indexAction() {
        $this->buildAssets();
        
        $numberPage = $this->request->getQuery("page", "int");
        $orderby = $this->request->getQuery('orderby');
        $order_field = Resource::indexOrderBy($this->view, $orderby);
        if (is_null($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }
        $view = $this->view;
        $view->orderby = $orderby;
        $view->page = $this->listPageNum($numberPage, 12, $order_field);
        $this->ctx->pickView($view, 'resource/index');
        $view->setVar('myModule', '/secure/');
        $view->setVar('myController', '/secure/resource/');
    }
    
    public function newAction()
    {
        $this->buildAssets();
        $req = $this->request;
        $view = $this->view;
        $this->ctx->pickView($view, 'resource/new');
        
        if (!$req->isPost())
        {
            $form = new ResourceForm();
            $view->form = $form;
        }
        else {
            $form = new ResourceForm();
            if (!$form->isValid($req->getPost()))
            {
                $messages = $form->getMessages();

                foreach ($messages as $message) {
                    $this->flash->error($message);
                }
                $view->form = $form;
                return;
            }
            $res = new Resource();
            $res->name = $req->getPost('name');
            $res->action = $req->getPost('action');
            if (!$res->save())
            {
                $this->flash->error($res->getMessage());
                $view->form = $form;
                return;
            }
            $this->dispatcher->forward([ 'action' => 'index']);
        }
    }
    
    private function assignFromPost($res) {
        $req = $this->request;
        $res->name = $req->getPost('name', 'string');
        $res->action = $req->getPost('action', 'string');
    }
        /**
     * Assign using existing record
     * @param type $id
     */
    private function updatePost($id) {
        $form = new ResourceForm();
        $req = $this->request;
        $post = $this->request->getPost();
        if ($form->isValid($post)) {
            // update the record
            $res = Resource::findFirst($id);
            $this->assignFromPost($res);

            if ($res->update()) {
                $this->flash->success("Resource was updated successfully");
                $form = new ResourceForm($res);
                $this->view->form = $form;
            }
        }
    }
    /** Post an update */
    public function editAction($id) {
        $this->buildAssets();
        $view = $this->view;
        $view->resid = $id;
        $view->title = 'Edit Resource '. $id;
        $this->ctx->pickView($view, 'resource/edit');
        $view->myController = '/secure/resource/';
        
        if ($this->request->isPost()) {
            try {
                $this->updatePost($id);
            } catch (\Exception $e) {
                $this->flash->error($e->getMessage());
            }
        } else if ($id > 0) {
            $res = Resource::findFirst($id);
            if ($res) {
                $form = new ResourceForm($res);
                $view->form = $form;
            }
        }
    }
}