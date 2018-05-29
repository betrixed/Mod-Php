<?php

namespace Page\Controllers;

use Page\Forms\LinkForm;
use Page\Models\Links;
use Mod\PageInfo;
use Page\Models\Blog;


class LinksController extends \Phalcon\Mvc\Controller {
    protected function pickView($pick)
    {
        $view = $this->view;
        $this->ctx->pickView($view, 'links/' . $pick);
        $view->myModule = "/page_admin/";
        $view->myController = "/page_admin/links/";
    }
    /**
     * Return page of results
     * @param type $numberPage
     * @param type $pageRows
     */
    protected function listPageNum($numberPage, $pageRows, $orderby) {
        $start = ($numberPage - 1) * $pageRows;
        //SQL_CALC_FOUND_ROWS
        $sql = "select SQL_CALC_FOUND_ROWS b.* "
                . " from links b"
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
    public function initialize() {
        $this->view->setTemplateBefore('id');
    }
    protected function handleIndexPost()
    {
        $req = $this->request;
        $op = $req->getPost('link_enable','int');
        $values = $req->getPost();
        foreach($values as $key => $val)
        {
            if (substr($key,0,3)=='lid')
            {
                $id = intval($val); 
                $rec = Links::findFirst($id);
                if ($rec)
                {
                    $rec->enabled = $op;
                    $rec->update();
                }
            }
        }
    }
    public function indexAction() {
        if ($this->request->isPost())
        {
            $this->handleIndexPost();
        }
        $this->buildAssets();  
        $numberPage = $this->request->getQuery("page", "int");
        $orderby = $this->request->getQuery('orderby');
        $order_field = Links::indexOrderBy($this->view, $orderby);
        if (is_null($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }
        
        $this->pickView('index');
        $view = $this->view;
        $this->view->orderby = $orderby;
        $this->view->page = $this->listPageNum($numberPage, 12, $order_field);
    }

    public function newAction() {
        $this->buildAssets();
        $this->editAssets();
        $req = $this->request;
        $this->view->pick('links/edit');
        if (!$req->isPost())
        {
           
 
            
            $this->view->linkid = 0;
            $this->view->title = 'New link';
            
            $this->view->linkform = new LinkForm();
            $this->view->refid = 0;
                
            return;
        }
        //$link = new Links();
        $this->createFromForm();
    }
    protected function buildAssets()
    {
        $this->elements->addAsset('bootstrap');
    }
    protected function editAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
        $elements->addAsset('jquery-form');
        $elements->addAsset('summer-note');
        $elements->addAsset('datetime-picker');
    }
    /**
     * The id passed here is the key of a blog record.
     * Find and edit, or create a links record of urltype 'Blog',
     * and refid equal to the blog record. This breaks strict relational model.
     * The url will bet set to article/<blog.title_clean>
     * @param type $id
     */
    public function blogAction($id) {
        $this->buildAssets();
        $this->editAssets();
        $req = $this->request;
        $this->pickView('links/edit');
    
        if ($req->isPost()) {
            $linkid = $req->getPost('id', 'int'); //replace id with link id
            if (isset($linkid) && ($linkid > 0)) {
                // doing an update
                $this->updatePost($linkid);
            } else {
                // create new
                $link = new Links();
                $link->refid = $id; // from blog id
                $this->createFromPost($link);
                $myform = new LinkForm($link);
                $this->view->linkform = $myform;
                $this->view->refid = null;
            }
        } else {
            /* find or create */
            $blog = Blog::findFirst($id);
            if ($blog) {
                $link = Links::findFirst("urltype='Blog' and refid = " . $id);
                if ($link) {
                    $myform = new LinkForm($link);
                } else {
                    $myform = new LinkForm();
                    $elements = $myform->getElements();
                    $elements['refid']->setDefault((int) $id);
                    $elements['sitename']->setDefault('Here');
                    $elements['url']->setDefault("/article/" . $blog->title_clean);
                    $elements['urltype']->setDefault('Blog');
                    
                    $elements['title']->setDefault($blog->title);
                    $text = IntroText($blog->article, 150);
                    $filter = new \Phalcon\Filter();
                    $text = $filter->sanitize($text, "string");
                    $elements['summary']->setDefault($text);
                }
                /* setup new form */
                $this->view->refid = $id;
                $this->view->linkform = $myform;
            } else {
                // no blog to work with, what are we doing here?
                $this->response->redirect($this->myController ."new");
            }
        }
    }

    /**
     * Delete and return to index
     * @param type $id
     */
    public function deleteAction() {
        $req = $this->request;
        if ($req->isPost()) {
            try {
                $post = $req->getPost();
                $id = $req->getPost("id", "int");
                $link = Links::findFirst($id);
                if ($link) {
                    $link->delete();
                }
                $this->flash->success('link deleted');
            } catch (Exception $ex) {
                $this->flash->error($e->getMessage());
            }
            $this->response->redirect($this->myController ."index");
        }
    }
    
    private function createFromForm()
    {
        $form = new LinkForm();
        $req = $this->request;
        $post = $this->request->getPost();
        $this->view->linkform = $form;
        $this->view->linkid = 0;
        $this->view->refid = 0;
        if ($form->isValid($post)) {
            // update the record
            $link = new Links();
            
            $this->assignFromPost($link);
            $link->date_created = date('Y-m-d H:i:s');
            $link->enabled = 1;
            if ($this->createFromPost($link)){
                $this->view->disable();
                $this->response->redirect($this->myController . "edit/" . $link->id);
                return false;
            }
        }     
        return true;
    }
    private function createFromPost($link) {
        $link->date_created = date('Y-m-d H:i:s');
        $this->assignFromPost($link);
        if ($link->save()) {
            $this->flash->success("Link was added successfully");
            return true;
        } else {
            foreach ($link->getMessages() as $message) {
                $this->flash->error($message);
            }
            return false;
        }
    }
    private function assignFromPost($link) {
        $req = $this->request;
        $link->url = $req->getPost('url');
        if (strlen($link->url)==0)
        {
            $link->url = '';
        }
        $link->urltype = $req->getPost('urltype', 'string');
        $link->sitename = $req->getPost('sitename', 'string');
        $link->title = $req->getPost('title', 'string');
        $link->summary = $req->getPost('summary');
        $link->enabled = $req->getPost('enabled','int');
        $link->date_created = $req->getPost('date_created');
        $test = (int) $req->getPost('refid', 'int');
        $link->refid = $test;
        
        /* if ($link->urltype == 'Front') {
            $sql = "delete from links where urltype='Front'";

            if (isset($link->id)) {
                $sql .= " and id <> " . $link->id;
            }
            $db = $this->getDb();
            $db->execute($sql);
        }
        */
        
    }

    /**
     * Assign using existing record
     * @param type $id
     */
    private function updatePost($id) {
        $form = new LinkForm();
        $req = $this->request;
        $post = $this->request->getPost();
        if ($form->isValid($post)) {
            // update the record
            $link = Links::findFirst($id);
            $this->assignFromPost($link);

            if ($link->update()) {
                $this->flash->success("Link was updated successfully");
                $form = new LinkForm($link);
                $this->view->linkform = $form;
                $this->view->linkid = $id;
                $this->view->refid = $link->refid;
            }
        }
    }

    /** Post an update */
    public function editAction($id) {
        $this->buildAssets();
        $this->editAssets();

        $this->view->linkid = $id;
        $this->view->title = 'Edit link '. $id;
        if ($this->request->isPost()) {
            try {
                $this->updatePost($id);
            } catch (\Exception $e) {
                $this->flash->error($e->getMessage());
            }
        } else if ($id > 0) {
            $link = Links::findFirst($id);
            if ($link) {
                $form = new LinkForm($link);
                $this->view->refid = $link->refid;
                $this->view->linkform = $form;
            }
        }
    }


}
