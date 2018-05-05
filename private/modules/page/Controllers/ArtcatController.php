<?php

namespace Page\Controllers;

use \Phalcon\Mvc\View;

use Page\Models\BlogCategory;
use Page\Models\Blog;
/**
 * Left side , sortable list of articles belonging to one category.
 * Main - selected article controller 'artcat' / index / 'category name'
 */
class ArtcatController extends \Phalcon\Mvc\Controller {
    protected function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
        
    }
    
    public function indexAction($catclean)
    {
        $this->buildAssets();
        // * get the category id associated with $catclean
        $cat = BlogCategory::findFirstByname_clean($catclean);
        if (!$cat)
        {
            $this->flash->error("Category not found");
            $this->response->redirect("index/index");
            return;
        }
        
        //* get all the blogs with category id
        $db = $this->db;
$sql=<<<EOD
select b.id, b.date_published, b.title, b.title_clean from
 blog b join blog_to_category bc 
  on b.id = bc.blog_id and bc.category_id = $cat->id 
  and b.enabled = 1
 order by b.issue desc, b.id asc          
EOD;
        $stmt = $db->prepare($sql);
        //$stmt->bindValue(':catid', $cat->id, \PDO::PARAM_INT);
        if (!$stmt->execute())
        {
            $this->flash->error("Articles not found");
        }
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $stmt->fetchAll();  
        
        $view = $this->view;
        
        $view->blogs = $results;
        if (count($results) > 0)
        {
            $view->firstId = $results[0]->id;
        }
        else {
            $view->firstId = "";
        }
        $view->catclean = $catclean;
        $view->cattitle = $cat->name;
        $this->ctx->pickView($view, 'cat/index');    
    }
    
    public function fetchAction($id)
    {
        if(!$this->request->isAjax())
        {
            $this->flash->error('Not ajax request');
            return;
        }
        $blog = Blog::findFirstByid($id);

        $view = $this->view;
        $view->blog = $blog;
        $view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $this->ctx->pickView($view, 'cat/fetch');
    }
    
};


