<?php
namespace Page\Controllers;


use Phalcon\Mvc\View;
use Phalcon\Db as PDO;
use Page\Models\Links;
use Mod\Path;

use Phalcon\Events\Manager;

class HomeController extends \Phalcon\Mvc\Controller
{
    protected $linkId;
    
    protected function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
        //$elements->addAsset('salvattore');

        $elements->moduleCssList(['novosti.css','sbo.css'], 'page');

    }
    public function initialize()
    {
        $this->tag->prependTitle('Home-');
    }
    public function navAction()
    {
        // return nav bar content appropriate for identity
        $this->pickView("partials/nav");
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);   
    }
    
    public function mainHtmlParams()
    {
        $params = [];
        $db = $this->db;
        // links to recent blog articles
                // recent remote links below front page article
$sql=<<<EOD
select id, url, title, sitename, summary, urltype, date_created 
  from links
  where (urltype='Remote' or urltype='Front' or urltype='Blog') 
  and enabled = 1
  order by date_created desc
 limit 0, 20
EOD;
        $fq = $db->query($sql);
        //$fquery->execute();
        $fq->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $linkrows = $fq->fetchAll(); 
        if (!$linkrows)
        {
            $params['linkrowsct'] = 0;
        }
        else {
            $params['linkrowsct'] = count($linkrows);
            $params['linkrows'] = $linkrows;
        }
        //$params['isMobile'] = $this->isMobile();
        return $params;
    }
    public function mainHtmlContent() {
        // view to render
        return $this->anyHtmlContent('home/home', $this->mainHtmlParams());
    }
    
        // return cached or generate HTML for side-links, events, recent articles
    protected function sideHtmlParams() {
        // view to render
        
        $params = [];
        $db = $this->db;
        // links to recent, featured blog articles
        $mquery = $db->query(
                "select b.id, b.title, b.date_published, b.title_clean" 
                . " from blog b where b.enabled=1 and b.featured=1"
                . " order by date_published desc limit 5"
        );
        $mquery->setFetchMode(PDO::FETCH_OBJ);
        $blog = $mquery->fetchAll();
        
        $params['recent'] = $blog;

        // get pending events
        $events = $db->prepare(
                "select e.id, e.fromTime, e.blogId, b.title, b.title_clean"
                . " from event e"
                . " join blog b on b.id = e.blogId"
                . " where e.fromTime > :evtdate order by e.fromTime"
        );

        $events->bindValue(':evtdate', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $events->execute();
        $events->setFetchMode(\PDO::FETCH_OBJ);

        $result = $events->fetchAll();
        if (!$result) {
            $result = array();
        }
        $params['events'] = $result;
        // current links designated campaigns
        $campaigns = $db->query(
                "select id, url, title, date_created from links"
                . " where urltype='Campaign' and enabled = 1"
                . " order by date_created"
        );
        $campaigns->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $result2 = $campaigns->fetchAll();
        if (!$result2) {
            $result2 = [];
        }
        $params['campaigns'] = $result2;
                // specific links for 'Side' display in full
        $asides = $db->query(
                "select id, url, title, date_created, summary from links"
                . " where urltype='Side' and enabled = 1"
                . " order by date_created"
        );
        
        $asides->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $result3 = $asides->fetchAll();
        if (!$result3)
        {
            $result3 = [];
        }

        $params['sides'] = $result3;
        //$params['isMobile'] = $this->isMobile();
        
        return $params;

      
    }
    protected function anyHtmlContent($controllerAction, $params) {
         $view = $this->makeCacheView($controllerAction);
         $view->disableLevel(
            [
                View::LEVEL_LAYOUT      => true,
                View::LEVEL_MAIN_LAYOUT => true,
            ]
        );
        $view->setVars($params); 
        $view->start();
        $split = explode('/', $controllerAction);
        $view->render($split[0], $split[1]);
        $content = ob_get_contents();
        $view->finish();
        return $content;
    }
    public function sideHtmlContent()
    {
        return $this->anyHtmlContent('home/side', $this->sideHtmlParams());
    }
    public function linkHtmlContent() 
    {
        $link = Links::findFirstById($this->linkId);
        $this->pickView('index/link');
        $params = array();
        $this->view->link = $link;
        
        $content = $this->view->getRender('home', 'link', $params, function ($view) {
            $view->setRenderLevel(View::LEVEL_LAYOUT);
        });   
        return $content;
    }
    
    
    public function linkAction($id)
    {
        if ($this->request->isAjax())
        {
           $this->linkId = $id;
           return $this->getCacheResponse("link_$id.html", [$this,'linkHtmlContent']);
        }
        return false;        
    }
     public function homeAction()
    {
        if ($this->request->isAjax())
        {
            return $this->getCacheResponse("home_wide_content.html", [$this,'mainHtmlContent']);
        }
        return false;       
    }   
    public function sideAction()
    {
        if ($this->request->isAjax())
        {
            return $this->getCacheResponse("side_index.html", [$this,'sideHtmlContent']);
        }
        echo "Expected AJAX" ;
    }
    
    public function makeCacheView($controllerAction)
    {
        $view = new View();
        $view->setDI($this->getDI());
        $view->registerEngines([".volt" => 'volt']);
       
        $mod = $this->ctx->activeModule;
        
        // make ordered list of paths to look for $controllerAction
        $viewsDir = $mod->viewsDir;
        if (is_array($viewsDir)) {
            /** For some reason, array of paths can bugger up getting
              content out of render
             This is because all possible matches will get compiled
             and one of them might give errors.
             Therefore, only pass the first match.
             */
            $match = Path::findFirstPath($viewsDir,$controllerAction,['.volt']);
            if (is_array($match)) {
                $viewDir = $match[0];
            }
            else {
                $viewDir = $viewsDir[0]; // Instead of last?
            }
            $view->setViewsDir($viewDir);
            $view->myDir = $viewDir . $controllerAction;
        }
        else {
            $view->setViewsDir($viewsDir);
        }
        $view->pick($controllerAction);
        $this->ctx->setViewUser($view);
        /*
        if (is_string($viewsDir)) {
            $testArray = [$mod->viewsDir, 
                            PCAN_DIR . "/views_" . $mod->name . "/", 
                            PCAN_DIR . "/views/"];
        }
        else if (is_array($viewsDir)){
            $testArray = $viewsDir;
        }
        else {
            throw new \Exception('Module viewsDir not set');
        }
        $this->findViewPath($view, $testArray, $controllerAction, true);
         
         */
        //
        return $view;
    }
    
    public function pageHtmlContent()
    {
        $ctx = $this->ctx;
        $sideContent = $ctx->getCacheHtml("side_index.html", [$this,'sideHtmlContent']);
        $mainContent = $ctx->getCacheHtml("home_wide_content.html", [$this,'mainHtmlContent']);    

        $view = $this->makeCacheView('home/mobile_index');
        $view->setLayoutsDir('layouts/');
        $view->setLayout('mobile');
        $ctx->setViewUser($view);
        //$view->setTemplateBefore('mobile');
        $view->setRenderLevel(View::LEVEL_MAIN_LAYOUT);
        ob_clean();
        $view->start();
        //$params['isMobile'] = $view->isMobile;
        $params['sideCacheHtml'] = $sideContent;  
        $params['mainCacheHtml'] = $mainContent;
        
        
        $view->setVars($params);
        $view->render('home', 'mobile_index');
        $content = ob_get_contents();
        //ob_end_clean();
        $view->finish();

        return $content;
    }
    public function indexAction()
    {
        $this->buildAssets();

        $content = $this->pageHtmlContent();
        $response = new \Phalcon\Http\Response();
        $response->setContent($content);
        return $response;
    }
    

        
}