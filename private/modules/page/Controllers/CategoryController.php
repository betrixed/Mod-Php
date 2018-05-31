<?php

namespace Page\Controllers;

/**
 * Provide Canonical Access based on title to blog articles
 *
 * @author Michael Rynn
 */



use Page\Models\BlogCategory;
use Mod\PageInfo;
use Page\Forms\BlogCategoryForm;

class CategoryController  extends \Phalcon\Mvc\Controller {

    public $cat_form;
    
    public function pickView($name) {
        $view = $this->view;
        $this->ctx->pickView($view, 'category/' . $name);
        $view->myController = '/page_admin/category/';
        $view->myURL = $view->myController . $name . '/';
    }
    public function buildAssets() {
        $this->elements->addAsset('bootstrap');
    }
    public function indexAction()
    {    
        $this->buildAssets();
        $this->pickView('index');
        $numberPage = $this->request->getQuery("page", "int");
        if (is_null($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }
        $grabsize = 16;
        $start = ($numberPage - 1) * $grabsize;
        
        $this->view->title = "Category Index";
        $db = $this->db;
        $sql = "select SQL_CALC_FOUND_ROWS * from blog_category"
                . " limit " . $start . ", " . $grabsize;
        
        $qcat = $db->query($sql);
        $qcat->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $qcat->fetchAll();
        
        $cquery = $db->query("SELECT FOUND_ROWS()");
        $cquery->setFetchMode(\Phalcon\Db::FETCH_NUM);
        $maxrows = $cquery->fetch();
        
        $paginator = new PageInfo($numberPage, $grabsize, $results, $maxrows[0]);
        $this->view->page = $paginator;
        
    }
    public function createAction()
    {    
        $this->buildAssets();
        $this->pickView('create');
        $request = $this->request;
        $this->setupForm(); 
        $this->view->form = $this->cat_form;
        $this->view->title = "Category Create";
        if ($request->isPost()!=false)
        {
            $category = new BlogCategory();
            $this->cat_form->bind($_POST, $category);
            if ($this->cat_form->isValid())
            {
                if ($category->enabled=='Y')
                {
                    $category->enabled = 1;
                }
                $category->date_created = date('Y-m-d H:i:s');
                if (!$category->create())
                {
                    foreach ($category->getMessages() as $message) {
                        $this->flash->error($message);
                    }
                    return false;                    
                }
                else {
                    $this->dispatcher->forward(["action"=>"index"]);
                }
                
            }
        }    
    }
    
    protected function doPost($id)
    {
        
        
        // related to blog by id fields, so can edit name, name_clean or enabled
        $req = $this->request;
        $check_id = (int)  $req->getPost('id','int');
        $new_name = $req->getPost('name');
        $new_nameclean = $req->getPost('name_clean');
        $new_enable = (int) $req->getPost('enabled','int');
        
        $category = BlogCategory::findFirstByid($check_id);
        if (!$category)
        {
            $this->flash->error("Unabled to find category " . $id);
            return;
        }
        if (($category->name != $new_name) ||
            ($category->name_clean != $new_nameclean) ||
            ($category->enabled != $new_enable)
            )
        {
            $category->name = $new_name;
            $category->name_clean = $new_nameclean;
            $category->enabled = $new_enable;
            
            if ($category->save())
            {
                $this->flash->success("Altered category");
            }
            else
            {
                $this->flash->error("Unabled to change category");
            }
        }
        $this->view->form = new BlogCategoryForm($category);               
        $this->view->catid = (int) $check_id;
    }
    public function editAction($id)
    {
        $this->buildAssets();
        $this->pickView('edit');
        if (!$this->request->isPost())
        {
            $category = BlogCategory::findFirstByid($id);

            $this->view->form = new BlogCategoryForm($category);
            $this->view->catid = (int) $id;
        }
        else {
            
            $this->doPost();
        }
        
    }
    private function setupForm()
    {
        
        $cform = new BlogCategoryForm();
        $cform->initialize();
        $this->cat_form = $cform;
        
    }
}