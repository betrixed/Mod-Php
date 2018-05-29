<?php

/*
See the "licence.txt" file at the root "private" folder of this site
*/
namespace Page\Controllers;

use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\View;

use Phalcon\Paginator\Adapter\Model as Paginator;

use Page\Models\Blog;
use Page\Models\BlogComment;
use Page\Models\Links;
use Mod\PageInfo;
use Page\Models\Event;
use Page\Forms\CommentForm;
use Mod\Text;

/*use Pcan\Plugins\SummerNote;
use Pcan\Plugins\DateTimePicker;
use Pcan\Plugins\JQueryForm;
*/

class EditController extends \Phalcon\Mvc\Controller {

    public $posted;

    protected function pickView($pick)
    {
        $view = $this->view;
        $this->ctx->pickView($view, 'edit/' . $pick);
        $view->myModule = "/page/";
        $view->myController = "/page/edit/";
    }
    protected function buildAssets()
    {
       $this->elements->addAsset('bootstrap');
    }
    /**
     * Make title_clean unique by appending more characters.
     * Do not need to do this if title has not changed.
     * @param type $base_name
     * @param type $existingid
     */
    private function unique_title_url($blog, $slug) {
        $sql = 'select count(*) from blog where title_clean = :tc';
        $isUpdate = !is_null($blog->id) && ($blog->id > 0);

        if ($isUpdate) {
// exclude self from search, in case of no change?
            $sql .= ' and id <> :bid';
        }
        $db = $this->db;
        $stmt = $db->prepare($sql);
        $tryCount = 0;

        $candidate = $slug;
        $date = new \DateTime($blog->date_published);
        if ($isUpdate) {
            $stmt->bindValue(':bid', $blog->id, \PDO::PARAM_INT);
        }
        while ($tryCount < 5) {
            $stmt->bindValue(':tc', $candidate, \PDO::PARAM_STR);
            $stmt->execute();
            $count_star = $stmt->fetch(\PDO::FETCH_NUM);
            if ($count_star[0] == 0) {
                break;
            } else {
                if ($tryCount == 0) {
                    $slug .= '-' . date('Ymd', $date->getTimestamp());
                    $candidate = $slug;
                } else {
                    $candidate = $slug . '-' . $tryCount;
                }
            }
            $tryCount += 1;
        }
        $blog->title_clean = $candidate;
    }

    private function getEvents($id) {
        $sql = "select e.* from event e where e.blogId = :blogId";
        $db = $this->db;
        $stmt = $db->query($sql,array('blogId' => $id));
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ); 
        $results = $stmt->fetchAll();
        if ($results) {
            return $results;
        } else
            return array();
    }


    private function getCategoryList($id) {
        $sql = "select c.id, c.name, b.blog_id from blog_category c"
                . " left outer join blog_to_category b on b.category_id = c.id"
                . " and b.blog_id = :blogId order by c.name";
        $db = $this->db;
        $stmt = $db->query($sql,array('blogId' => $id));
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $stmt->fetchAll();  
       
        $this->view->cat_blogid = $id;
        
        if ($results) {
            $values = "";
            foreach($results as $row)
            {
                if ($row->id > 0)
                {
                    if (!($row->blog_id > 0))
                    {
                        if (strlen($values) > 0) $values .= ", ";
                            $values .= $row->name; 
                    }  
                }
            }
            if (strlen($values)==0)
                $values = "(empty)";
            $this->view->cat_values = $values;
            return $results;
        } else
            return array();
    }

    private function getMetaTags($id) {
// setup metatag info
        $sql = "select m.id, m.meta_name,"
                . "m.template, m.data_limit, b.blog_id, b.content"
                . " from meta m"
                . " left join blog_meta b on b.meta_id = m.id"
                . " and b.blog_id = :blogId";
// form with m_attr_value as labels, content as edit text.
        $db = $this->db;
        $stmt = $db->query($sql,array('blogId' => $id));
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $stmt->fetchAll();       
        return $results;
    }

    public function initialize() {
        $this->view->setTemplateBefore('id');
        $this->posted = false;
        $this->myController = '/page/edit/';
    }

    public function deleteFileAction() {
        $request = $this->request;


        if ($request->isPost() && $request->isAjax()) {

            $file_id = $request->getPost('id', 'int');
            $blog_id = $request->getPost('blogid', 'int');
            if (isset($file_id)) {
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
                $this->pickView("upload");
                $di = \Phalcon\DI::getDefault();
                $config = $di->get('config');
                $upfile = FileUpload::findFirst(array(
                            "conditions" => "id=?0",
                            "bind" => array(0 => $file_id)
                ));

                if ($upfile) {
                    $path = $config->pcan->webDir . $upfile->path . $upfile->name;
                    $wasDeleted = false;
                    if (is_file($path)) {
                        $wasDeleted = unlink($path);
                    } else if (!file_exists($path)) {
                        $wasDeleted = true;
                    }
                    if ($wasDeleted) {
                        $upfile->delete();
                    }
                    $this->view->upfiles = FileUpload::find(array(
                                "conditions" => "blog_id = ?1",
                                "bind" => array(1 => $blog_id),
                    ));

                    $this->view->replylist = ['Deleted file ' . $upfile->name];
                }
            }
        }
    }

    public function categorytickAction() {
        $this->pickView("category");
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

        $request = $this->request;
        if ($request->isPost() && $request->isAjax()) {
            $blog_id = $request->getPost('blogid', 'int');
            $chkct = $request->getPost('chkct', 'int');
            $db = $this->db;
            $db->begin();
            $id = 0;
            // get all the existing category ids
            $existing = $db->query("select category_id from blog_to_category where blog_id = :blogId",array('blogId' => $blog_id));
            $existing->setFetchMode(\PDO::FETCH_OBJ);
            $results = $existing->fetchAll();
            
            $hasCategory = array();
            foreach($results as $row)
            {
                $hasCategory[(int) $row->category_id] = true;
            }
            
            
            $insertSql = "REPLACE INTO blog_to_category ( category_id, blog_id ) VALUES (:catId, :blogId)";
            $deleteSql = "DELETE IGNORE from blog_to_category where category_id = :catId and blog_id = :blogId";
            
            $insert = $db->prepare($insertSql);
            $delete = $db->prepare($deleteSql);
            $insert->bindValue(':blogId', $blog_id,  \PDO::PARAM_INT);
            $delete->bindValue(':blogId', $blog_id,  \PDO::PARAM_INT);
            // html form only returns the checked rows
            for ($ix = 1; $ix <= $chkct; $ix++) {

                $chkname = "cat" . $ix;
                $chkvalue = $request->getPost($chkname, 'int');
                if ($chkvalue > 0) {
                    if (!array_key_exists($chkvalue,$hasCategory))
                    {
                        $insert->bindValue(':catId', $chkvalue,  \PDO::PARAM_INT);
                        $insert->execute();
                    }
                    else {
                        $hasCategory[$chkvalue] = false;
                    }
                }
            }
            // delete unconfirmed values
            foreach($hasCategory as $key => $value)
            {
                if ($value)
                {
                    $delete->bindValue(':catId', $key,  \PDO::PARAM_INT);
                    $delete->execute();
                }
            }
            $db->commit();
            $this->view->categoryList = $this->getCategoryList($blog_id);
        }
       
    }
    
    protected function blogAssets()
    {
        $elements = $this->elements;
        $elements->addAssetNames(
                ['bootstrap', 'jquery-form', 'summer-note', 'datetime-picker']            
                    );
    }
    
    public function eventListAction() {
        $this->pickView("event");
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        
        $request = $this->request;
        $this->blogAssets();  

        
        if ($request->isPost() && $request->isAjax()) {
            $blog_id = $request->getPost('blogid', 'int');
            $event_op = $request->getPost('event_op');
            $chkct = $request->getPost('chkct', 'int');

            if ($chkct > 0) {
                $sql = "";
                $id = 0;
                switch ($event_op) {
                    case "enable":
                        $sql = "update event set enabled=1 where id=:id";
                        break;
                    case "disable":
                        $sql = "update event set enabled=0 where id=:id";
                        break;
                    case "remove":
                        $sql = "delete from event where id=:id";
                        break;
                }


                for ($ix = 1; $ix <= $chkct; $ix++) {

                    $chkname = "chk" . $ix;
                    $chkvalue = $request->getPost($chkname, 'int');
                    if ($chkvalue > 0) {

                        if ($id == 0) {
                            $db = $this->db;
                            $stmt = $db->prepare($sql);
                            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
                        }
                        $id = (int) $chkvalue;
                        $stmt->execute();
                    }
                }
            }
            $this->view->events = $this->getEvents($blog_id);
        } else {
            $this->flash->error('No Ajax');
            $this->view->events = array();
        }
    }

    public function eventAction() {
        $this->pickView("event");
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $request = $this->request;
        if ($request->isPost() && $request->isAjax()) {
            $blog_id = $request->getPost('event_blogid', 'int');
            $fromDate = $request->getPost('fromDate');
            $toDate = $request->getPost('toDate');
            $enabled = $request->getPost('event_enabled');

            $event = new Event();

            $event->blogId = $blog_id;

            $event->fromTime = $fromDate;
            if (strlen($toDate) > 0)
                $event->toTime = $toDate;
            $event->enabled = ($enabled == 'Y') ? 1 : 0;

            if (!$event->save()) {
                foreach ($event->getMessages() as $message) {
                    $this->flash->error($message);
                }
                return false;
            }
            $this->view->events = $this->getEvents($blog_id);
        } else {
            $this->view->events = array();
        }

        // check fromDate, toDate, enabled
    }

    public function uploadAction() {
        //$response->setHeader("Content-Type", "text/plain");
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);
        $reply = array();
        $blog_id = $this->request->getPost('blogid', 'int');
        if ($this->request->hasFiles() == true) {
            $uploads = $this->request->getUploadedFiles();
            $isUploaded = false;
//do a loop to handle each file individually
            // get destination
            $dest_dir = $this->request->getPost('up_dest');
            $dest_dir .= '/upload/';

            foreach ($uploads as $upload) {
//define a “unique” name and a path to where our file must go
                // $path = 'temp/' . md5(uniqid(rand(), true)) . '-' . strtolower($upload->getname());
                $fname = strtolower($upload->getname());
                $path = $dest_dir . $fname;
#move the file and simultaneously check if everything was ok
                $mimetype = $upload->getRealType();
                $filesize = $upload->getSize();
                $isUploaded = ($upload->moveTo($path)) ? true : false;

                if ($isUploaded) {
                    $reply[] = 'paste url: /' . $path;
                    $rupload = new FileUpload();
                    $rupload->path = $dest_dir;
                    $rupload->name = $fname;
                    $rupload->date_upload = date('Y-m-d H:i:s');
                    $rupload->file_size = $filesize;
                    $rupload->blog_id = $blog_id;
                    $rupload->mime_type = $mimetype;

                    $rupload->Save();
                } else {
                    $reply[] = '_failed_: ' . $path;
                }
            }
        } else {
            $reply = ['No files were transferred'];
        }
        $fileset = FileUpload::find(array(
                    "conditions" => "blog_id = ?1",
                    "bind" => array(1 => $blog_id),
        ));
        $this->view->upfiles = $fileset;

        $this->view->replylist = $reply;
    }

    public function indexAction() {
        $this->buildAssets();
        return $this->pageAction();
    }

    /**
     * Index action
     */
    public function pageAction() {
        $numberPage = $this->request->getQuery("page", "int");
        $category = $this->request->getQuery("catId","int");
        
        
        if (is_null($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }
        if(is_null($category))
        {
            $category = 0;
        } 
        else {
            $category = intval($category);
        }
        $order_field = Blog::viewOrderBy($this->view, $this->request->getQuery('orderby'));



        $grabsize = 12;
        $start = ($numberPage - 1) * $grabsize;
//SQL_CALC_FOUND_ROWS
        $sql = "select  SQL_CALC_FOUND_ROWS b.*, u.name as author_name, 1 as canEdit from blog b"
                . " left join users u on u.id = b.author_id";
        
        if ($category > 0)
        {
            $sql .= " inner join blog_to_category bc on bc.blog_id = b.id and bc.category_id = :catId";
        }
        $sql .= " order by " . $order_field
                . " limit " . $start . ", " . $grabsize;

        $db = $this->db;

        if ($category > 0)
        {
            $stmt = $db->query($sql,array('catId' => $category));
        }
        else {
            $stmt = $db->query($sql);
        }
        
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $stmt->fetchAll();
        $acl = $this->acl;
        $isEditor = $acl->hasRole('Editor');
        foreach($results as $r) {
            $r->canEdit = ((b.author_id == acl.userId) || $isEditor);
        }

        $cquery = $db->query("SELECT FOUND_ROWS()");
        $cquery->setFetchMode(\Phalcon\Db::FETCH_NUM);
        $maxrows = $cquery->fetch();

        $paginator = new PageInfo($numberPage, $grabsize, $results, $maxrows[0]);
        /*
          ob_clean();
          var_dump($paginator);
          $s = ob_get_clean();
          $this->flash->notice($s); */
        $view = $this->view;
        $this->pickView("index");
        
        $view->page = $paginator;

        $catquery = $db->query("select id, name from blog_category");
        $catquery->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $cat_items = $catquery->fetchAll();
        if (count($cat_items) == 0)
        {
            $cat_items = array();
        }
        $view->catItems = $cat_items;
        $view->catId = $category;
    }

    /**
     * Searches for blog
     */
    public function searchAction() {

        $numberPage = 1;

        if ($this->request->isPost()) {
            $query = Criteria::fromInput($this->di, "\Pcan\Models\Blog", $this->request->getPost());
            $this->persistent->parameters = $query->getParams();
        } else {
            $numberPage = $this->request->getQuery("page", "int");
        }

        $parameters = $this->persistent->parameters;
        if (!is_array($parameters)) {
            $parameters = array();
        }
        $parameters["order"] = "id";

        $blog = Blog::find($parameters);
        if (count($blog) == 0) {
            $this->flash->notice("The search did not find any blog");

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "index"
            ));
        }

        $paginator = new Paginator(array(
            "data" => $blog,
            "limit" => 10,
            "page" => $numberPage
        ));

        $this->view->page = $paginator->getPaginate();
    }

    /* return true any value at all came back from checkbox */

    private function int_bool($pvar) {
        if (is_null($this->request->getPost($pvar)))
            return 0;
        else
            return 1;
    }

    private function checked_bool($pvar) {
        $chk = $this->int_bool($pvar);
        if ($chk > 0)
            return "checked";
        else
            return;
    }
    private function createNewBlog()
    {
        $secId =  $this->acl;
        
        if (!in_array('Editor',$secId->roleList))
        {
             $this->flash->error("You do not have role Editor");
            return;
        }
        $blog = new Blog();
        $blog->author_id = $secId->userId;
        $blog->views = 0;
        $blog->date_published = date('Y-m-d H:i:s');
        $blog->date_updated = date('Y-m-d H:i:s');
        $blog->featured = 0;
        $blog->enabled = 0;
        $blog->comments = 1;
        $this->setBlogFromPost($blog);

        if (!$blog->save()) {
            foreach ($blog->getMessages() as $message) {
                $this->flash->error($message);
            }

            return false;
        }

        $this->flash->success("blog was created successfully");
        $this->response->redirect($this->myController . "blog/" . $blog->id);
    }
    /**
     * Displayes the creation form
     */
    public function newAction() {
        $this->buildAssets();
        if (!$this->request->isPost()) {
// default to new.volt
            $view = $this->view;
            $this->pickView("new");        
            return;
        }
        
        $this->createNewBlog();

    }

// standard updates from edit or new.
    private function setBlogFromPost(&$blog) {
        $newTitle = $this->request->getPost('title', 'striptags');
        $oldTitle = $blog->title;
        $titleChanged = ($oldTitle !== $newTitle);
        $blog->title = $newTitle;
        $newUrl = '';
        $lock_url = $this->request->getPost('lock_url','int');
        
        $autoUrl = (!is_null($lock_url) || strlen( $blog->title_clean )== 0) ? true : false;
        
        if ($autoUrl && !is_null($blog->id)) {
            $newUrl = $this->request->getPost('title_clean', 'striptags');

            if ($newUrl != $blog->title_clean) {
                $this->unique_title_url($blog, $newUrl);
                $autoUrl = False;
            }
        }

        if ($titleChanged && $autoUrl) {
            $this->unique_title_url($blog, Text::url_slug($blog->title));
        }
        $blog->article = $this->request->getPost("article");
        
        $blog->style = $this->request->getPost('style');
        $blog->issue = $this->request->getPost('issue','int');
        
        // only do this if can update these values
        //if ($this->isApprover($blog->author_id)){
            $blog->featured = $this->int_bool('featured');
            $blog->enabled = $this->int_bool('enabled');
            $blog->comments = $this->int_bool('comments');
        //}
        $blog->date_updated = date('Y-m-d H:i:s');
    }

    private function setTagFromBlog($blog) {
        $this->view->id = $blog->id;
        $this->tag->setDefault("id", $blog->id);
        $this->tag->setDefault("title", $blog->title);
        $this->tag->setDefault("article", $blog->article);
        $this->tag->setDefault("title_clean", $blog->title_clean);
        $this->tag->setDefault("author_id", $blog->author_id);
        $this->tag->setDefault("date_published", $blog->date_published);
        $this->tag->setDefault("date_updated", $blog->date_updated);
        $this->tag->setDefault("style", $blog->style);
        $this->tag->setDefault("issue", $blog->issue);
        //$this->tag->setDefault("lock_url", 1);
// Checkbox field needs a default
        $this->tag->setDefault("featured", 1);
        $this->tag->setDefault("enabled", 1);
        $this->tag->setDefault("comments", 1);
    }

    private function getView($id) {
        $blog = Blog::findFirst("id = " . $id);
        if (!$blog) {
            $this->flash->error("blog was not found");

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "index"
            ));
        }
        $this->setTagFromBlog($blog);
        $this->view->blog = $blog;
    }
    private function isApprover($authorId)
    {
        $secId = $this->acl;
        $this->canEdit = in_array('Editor', $secId->roleList);
        return $this->canEdit && ($authorId !== $secId->userId);
    }
    protected function getBlogFiles($bid)
    {
        $sql = 'select * from file_upload where blog_id = :bid';
        
        $db = $this->db;
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':bid', $bid, \PDO::PARAM_INT);
        if (!$stmt->execute())
            return false;
        $stmt->setFetchMode(\PDO::FETCH_CLASS, "FileUpload");
        return $stmt->fetchAll();
 
    } 
    /**
     * Make an edit form for blog $id
     * @param type $id
     * @return type null
     */
    private function editForm($id) {
        $this->getView($id);
        
        $view = $this->view;
        
        $blog = $view->blog;
        $view->isApprover = $this->isApprover($blog->author_id);

        if (!$this->canEdit) {
            $this->response->redirect("blog/comment/" . $id);
            return;
        }

        $view->title = 'Edit blog ' . $blog->id;
// setup metatag info

        $view->metatags = $this->getMetaTags($id);

        $fileset = $this->getBlogFiles($id);

        $view->upfiles = $fileset;
        $view->events = $this->getEvents($id);
        $view->categoryList = $this->getCategoryList($id);
        
        $this->pickView('note');
    }

    /**
     * Make an edit form for blog $id
     * @param type $id
     * @return type null
     */
    private function noteForm($id) {
         
        
        $this->getView($id);
        $blog = $this->view->blog;

        $this->view->title = 'Note blog ' . $blog->id;
// setup metatag info

        $this->view->metatags = $this->getMetaTags($id);

        $fileset = $this->getBlogFiles($id);
        $this->view->upfiles = $fileset;
        $this->view->events = $this->getEvents($id);
        $this->view->categoryList = $this->getCategoryList($id);
    }
    /**
     * Edits a blog
     *
     * @param string $id
     */
    public function blogAction($id) {
        if ($this->posted)
            return true;
        $view = $this->view;
        $this->pickView('note');
        
        if (!$this->request->isPost()) {
            $this->editForm($id);
        } else {
            return $this->updatePost($id);
        }
    }

    public function notePost($id)
    {

        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "index"
            ));
        }
// check match?
        $check_id = $this->request->getPost("id");
        $blog = Blog::findFirstByid($id);
        if (!$blog) {
            $this->flash->error("blog does not exist " . $id);

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "index"
            ));
        }
// set updatable things
        $this->setBlogFromPost($blog);

        if (!$blog->save()) {

            foreach ($blog->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "edit",
                        "params" => array($blog->id)
            ));
        }        
    }
    public function noteAction($id) {
        
        if ($this->posted)
            return true;
        $view = $this->view;
        $this->pickView("note");
        
        
        $this->blogAssets();
        
        if (!$this->request->isPost()) {
            $this->noteForm($id);
        } else {
            return $this->updatePost($id);
        }            
    }
    /**
     * View a blog
     *
     * @param string $id
     */
    public function commentAction($id) {
        //$id = $this->dispatcher->getParam("id");
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
        $identity = $this->session->get('auth-identity');
        $user_id = null;
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

    /**
     * Creates a new blog
     */
    public function createAction() {

        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "index"
            ));
        }

        $blog = new Blog();

        $blog->id = $this->request->getPost("id");
        $blog->author_id = $this->request->getPost("author_id");            
        setBlogFromPost($blog);
        
        $blog->date_published = date('Y-m-d H:i:s');
        $blog->views = $this->request->getPost("views");

        if (!$blog->save()) {
            foreach ($blog->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "new"
            ));
        }

        $this->flash->success("blog was created successfully");
        $this->posted = true;
        return $this->dispatcher->forward(array(
                    "controller" => "blog",
                    "action" => "edit"
        ));
    }

    /**
     * Saves a blog edited
     *
     */
    public function updatePost($id) {

        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "index"
            ));
        }
// check match?
        $check_id = $this->request->getPost("id");
        $blog = Blog::findFirstByid($id);
        if (!$blog) {
            $this->flash->error("blog does not exist " . $id);

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "index"
            ));
        }
// set updatable things
        $this->setBlogFromPost($blog);

        if (!$blog->save()) {

            foreach ($blog->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "edit",
                        "params" => array($blog->id)
            ));
        }
        $link = Links::findFirst("urltype='Blog' and refid = " . $blog->id);
        if ($link)
        {
            // update the link title and url automagically
            $link->title = $blog->title;
            $link->url = "/article/" . $blog->title_clean;
            $link->update(); // will fail if duplicate
        }
        $metatags = $this->getMetaTags($id);
        $db = $this->db;
        $inTrans = false;
        foreach ($metatags as $mtag) {
// key = metatag-#id
            $key = 'metatag-' . $mtag->id;

            $content = $this->request->getPost($key, 'striptags');

            if (is_null($content) || empty($content)) {
// link content record needs deleting ? 
                if (!isset ($mtag->blog_id)) {
                    if (!$inTrans) {
                        $db->begin();
                        $inTrans = True;
                    }
                    $sql = "delete from blog_meta where blog_id = :blogid"
                            . " and meta_id = :metaid";
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':blogid', (int) $blog->id, \PDO::PARAM_INT);
                    $stmt->bindValue(':metaid', (int) $mtag->id, \PDO::PARAM_INT);
                    $stmt->execute();
                }
            } else {
                if (!$inTrans) {
                    $db->begin();
                    $inTrans = True;
                }
                $sql = "replace into blog_meta (blog_id, meta_id, content)"
                        . " values(:blogid, :metaid, :content)";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':blogid', (int) $blog->id, \PDO::PARAM_INT);
                $stmt->bindValue(':metaid', (int) $mtag->id, \PDO::PARAM_INT);
                $stmt->bindValue(':content', $content, \PDO::PARAM_STR);
                $stmt->execute();
            }
        }
        if ($inTrans) {
            $db->commit();
        }

// get the metatag info from the POST
// reconstruct the view
        $this->editForm($id);
    }

    /**
     * Deletes a blog
     *
     * @param string $id
     */
    public function deleteAction($id) {

        $blog = Blog::findFirstByid($id);
        if (!$blog) {
            $this->flash->error("blog was not found");

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "index"
            ));
        }

        if (!$blog->delete()) {

            foreach ($blog->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                        "controller" => "blog",
                        "action" => "search"
            ));
        }

        $this->flash->success("blog was deleted successfully");

        return $this->dispatcher->forward(array(
                    "controller" => "blog",
                    "action" => "index"
        ));
    }
    


        

}
