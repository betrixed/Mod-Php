<?php
/**
 * @author Michael Rynn
 */
namespace Page\Controllers;

use Phalcon\Mvc\View;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;
use Phalcon\Db\Adaptor\Pdo\MySql;
use Phalcon\Db\Adaptor\Pdo;
use Phalcon\Db;

use Page\MetaTags;
use Page\Models\Blog;
/*
use Pcan\Models\BlogComment;
use Pcan\Forms\CommentForm;
use Pcan\Models\Blog;

use Pcan\Models\PageInfo as PageInfo;
*/

class ArticleController  extends \Phalcon\Mvc\Controller {
    
    protected function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
        //$elements->addAsset('masonry');
        $mod = $this->mod;
        if ($mod->exists('css')) {
            $elements->moduleCssList($mod->css->toArray(), $mod->name);
        }
    }
    
    /**
     * View a blog
     *
     * @param string $id
     */
    public function commentAction() {
        $id = $this->dispatcher->getParam("id");
//$logger =  \Phalcon\DI::getDefault()->get('logger');
//$logger->log("Read:indexAction " . $id, \Phalcon\Logger::DEBUG);

        if (is_null($id)) {
//$logger->log("null id ", \Phalcon\Logger::DEBUG);
            $this->searchAction();
            return;
        }
        $bid = intval($id);


        $this->view->blog = Blog::findFirstByid($bid);

// comments listing pages

        $numberPage = $this->request->getQuery("page", "int");

        if (is_null($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }
        $grabsize = 16;


        $paginator = BlogComment::getComments($numberPage, $grabsize, $bid);

// set  up some fields
        $this->view->page = $paginator;
        $comment = new BlogComment();
// default to current user
        $identity = $this->getIdentity();
        $user_id = 0;
        if (isset($identity) && isset($identity['id'])) {
            $user_id = $identity['id'];
            $comment->user_id = $user_id;
        }

        $this->view->user_id = $user_id;
        $comment->blog_id = $id;
        $this->view->form = new CommentForm($comment, array(
            'edit' => true
        ));
    }
    
    public function byTitleAction($name)
    {
        $this->buildAssets();
        $config = $this->config;
        /* look up name in canonical metatag */
        $db = $this->db;
        $sql = "select * from blog where title_clean = :tc";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':tc', $name, \PDO::PARAM_STR);
        $stmt->execute();
        $blog = new Blog();
        $stmt->setFetchMode(\PDO::FETCH_INTO, $blog);
        
        $result = $stmt->fetch();
        
        if (!$result)
        {
            $blog->title = "Article with value { /$name } not found";
            $blog->article = "The link was incorrect";
        }
        $v = $this->view;
        $this->ctx->pickView($v, 'article/byTitle');
        $this->tag->prependTitle($blog->title);
        
        $v->blog = $blog;
        //$v->pick('title/byTitle');

        $v->analytics = true;
        $meta = array();
        // fill the array up with article meta tags.
        $domainName = 'http://' . $config->pcan->domainName;
        
        $tags = new MetaTags($db, $domainName);
        $v->metadata = $tags->getTags($blog->id);
        $v->metaloaf = $tags->getMeta();
        
        //$canonical = $this->request->isSecure() ? 'https://' : 'http://';
        // Facebook likes reference to be only one of https or http
        $v->canonical = $domainName . "/article/" . $name;
    }
}
