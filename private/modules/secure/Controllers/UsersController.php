<?php

/**
 * @author Michael Rynn
 */

namespace Secure\Controllers;

use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;
use Phalcon\Tag;
use Phalcon\Db\Adaptor\Pdo\MySql;
use Phalcon\Db\Adaptor\Pdo;
use Phalcon\Db;
use Phalcon\Mvc\View;

use Mod\PageInfo;
use Secure\Models\Users;

use Secure\Forms\UsersForm;
use Secure\Models\UserEvent;
use Secure\Models\UserAuth;

class UsersController extends \Phalcon\Mvc\Controller {

    protected function getDb() {
        return $this->db;
    }
    protected function buildAssets()
    {
        $this->elements->addAsset('bootstrap');
    }
    /**
     * Index action
     */
    public function indexAction() {
        //return $this->viewAction($this->getUser()->userId);
        $this->buildAssets();
        $this->listAction();
    }

    public function viewAction($id) {
        if (is_null($id)) {
            $id = $this->getUser()->userId;
        }
        $user = Users::findFirstById($id);
        if (!$user) {
            $this->flash->error("User was not found");
            return $this->dispatcher->forward(array(
                        'action' => 'index'
            ));
        }

        if ($this->request->isPost()) {

            $user->assign(array(
                'name' => $this->request->getPost('name', 'striptags'),
                'profilesId' => $this->request->getPost('profilesId', 'int'),
                'email' => $this->request->getPost('email', 'email'),
                'banned' => $this->request->getPost('banned'),
                'suspended' => $this->request->getPost('suspended'),
                'active' => $this->request->getPost('active')
            ));

            if (!$user->save()) {
                $this->flash->error($user->getMessages());
            } else {

                $this->flash->success("User was updated successfully");

                //Tag::resetInput();
            }
        }

        $this->view->user = $user;

        $this->view->form = new UsersForm($user, array(
            'edit' => false,
            'myid' => $id
        ));
    }

    public function listAction() {
        $numberPage = $this->request->getQuery("page", "int");

        if (is_null($numberPage)) {
            $numberPage = 1;
        } else {
            $numberPage = intval($numberPage);
        }

        $grabsize = 16;
        $start = ($numberPage - 1) * $grabsize;
        //SQL_CALC_FOUND_ROWS
        $sql = "select  SQL_CALC_FOUND_ROWS u.* "
                . " from users u"
                . " order by u.name"
                . " limit " . $start . ", " . $grabsize;


        $db = $this->getDb();
        $db->connect();

        $stmt = $db->query($sql);
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $stmt->fetchAll();

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
        $view->page = $paginator;
        $this->ctx->pickView($view, 'users/index');
        $view->myController = "/secure/users/";
    }

    /**
     * Searches for users
     */
    public function oldAction() {
        $numberPage = 1;
        if ($this->request->isPost()) {
            $query = Criteria::fromInput($this->di, 'Pcan\Models\Users', $this->request->getPost());
            $this->persistent->searchParams = $query->getParams();
        } else {
            $numberPage = $this->request->getQuery("page", "int");
        }

        $parameters = array();
        if ($this->persistent->searchParams) {
            $parameters = $this->persistent->searchParams;
        }

        $users = Users::find($parameters);
        $k = count($users);
        if ($k == 0) {
            $this->flash->notice("The search did not find any users");
        } else {
            $this->flash->notice($k . " Users");
        }
        $paginator = new Paginator(array(
            "data" => $users,
            "limit" => 10,
            "page" => $numberPage
        ));

        $this->view->page = $paginator->getPaginate();
    }

    /**
     * Displays the new form
     */
    public function newAction() {
        $this->buildAssets();

        $this->persistent->conditions = null;


        if ($this->request->isPost()) {
            $this->createNew();
        } else {
            $view = $this->view;
            $this->ctx->pickView($view, 'users/new');
            
            $userForm = new UsersForm();
            $this->view->form = $userForm;
        }
    }

    public function getGroups($id) {
        $db = $this->getDb();

        $stmt = $db->query(
                "select A.groupId, G.name, A.status,"
                . " A.created_at, A.changed_at from user_auth A"
                . " join user_group G on G.id = A.groupId"
                . " where A.userId = " . $id);

        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $data = $stmt->fetchAll();
        return $data;
    }
    protected function addUserGroup($userid, $groupid)
    {
        $role = new UserAuth();
        $role->groupId = $groupid;
        $role->userId = $userid;
        if (!$role->save())
        {
            $f = $this->getDI()->getFlash();
            $f->error(implode(PHP_EOL , $role->getMessages()));
            return false;
        }
        else
            return true;
    }
    protected function deleteUserGroup($userid, $groupid)
    {
        $db = $this->getDb();
 $sql =<<<EOD
 delete from user_auth where userId = :uid and groupId = :gid
EOD;
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid', $userid, \PDO::PARAM_INT);
        $stmt->bindValue(':gid', $groupid, \PDO::PARAM_INT);
        $stmt->execute();
    }
    protected function getOtherGroups($id)
    {
        $db = $this->getDb();
$sql =<<<EOD
SELECT  G.name, G.id  from user_group G
 where G.name <> 'Guest' AND 
 G.name NOT IN (select U.name from 
     user_auth A join user_group U on U.id = A.groupId
     where A.userId = :uid) 
 order by name
EOD;
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        $results = $stmt->fetchAll();
        return $results;        
    }
    protected function getGroupsList($id)
    {
        $db = $this->getDb();
$sql =<<<EOD
SELECT  G.name, G.id, G.active from user_group G
 join user_auth A on A.userId = :uid
 and G.id = A.groupId and G.active = 1
 order by name
EOD;
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        $results = $stmt->fetchAll();
        return $results;
    }

    
    protected function buildGroupsView($id)
    {
        $this->buildAssets();
        $user = Users::findFirstById($id);
        if (!$user)
        {
            $this->view->user = null;
            $this->view->groups = [];
            return;
        }
        $this->view->user = $user;
        $this->view->groups = $this->getGroupsList($user->id);  
        $this->view->others = $this->getOtherGroups($user->id);
    }
    
    static function startsWith($str, $pre)
    {
        $len = strlen($pre);
        return (substr($str,0,$len) == $pre);
    }
    public function groupsAction($id)
    {
        if ($this->request->isGet())
        {
            $this->buildGroupsView($id);
        }
        else {
            $req = $this->request;
            $id = $req->getPost('id','int');
            $user = Users::findFirstById($id);
            if (!$user)
            {
                return;
            }
            $post = $req->getPost();
            $isDelete = $req->getPost('del_group');
            $isAdd = $req->getPost('add_group');
            foreach($post as $key => $change)
            {
                if ($this::startsWith($key,'dgp'))
                {
                    $gid = intval(substr($key,3));
                    $this->deleteUserGroup($user->id, $gid);
                }
                else if ($this::startsWith($key,'agp'))
                {
                    $gid = intval(substr($key,3));
                    $this->addUserGroup($user->id, $gid);
                }
            }
            $this->buildGroupsView($id);
        }
    }
    public function authAction($id, $groupId)
    {
        
    }
    public function getUserTable($tableName, $id) {
        $db = $this->getDb();

        $stmt = $db->query(
                "select * from " . $tableName . " where usersid = " . $id);
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $data = $stmt->fetchAll();
        return $data;
    }
    /**
     * Edits a user
     *
     * @param string $id
     */
    public function editAction($id) {
        $this->buildAssets();

        $user = Users::findFirstById($id);
        if (!$user) {
            $this->flash->error("User was not found");
            return $this->dispatcher->forward(array(
                        'action' => 'index'
            ));
        }

        if ($this->request->isPost()) {

            $user->assign(array(
                'name' => $this->request->getPost('name', 'striptags'),
                'email' => $this->request->getPost('email', 'email'),
                'status' => $this->request->getPost('status'),
                'mustChangePassword' => $this->request->getPost('mustChangePassword')
            ));

            if (!$user->save()) {
                $this->flash->error($user->getMessages());
            } else {

                $this->flash->success("User was updated successfully");

                //Tag::resetInput();
            }
        }
        $view = $this->view;

        $view->groups = $this->getGroups($user->id);
        // successLogins
        
        $sec = $this->acl;
        $view->successLogins = $sec->getUserEvents(UserEvent::PW_LOGIN, $user->id);

        // passwordChanges   
        $view->passwordChanges = $sec->getUserEvents(UserEvent::PW_CHANGE, $user->id);

        // resetPasswords 
        $view->resetPasswords = $sec->getUserEvents(UserEvent::PW_RESET, $user->id);

        $view->user = $user;

        $this->view->form = new UsersForm($user, array(
            'edit' => true
        ));
    }

    /**
     * This is for a manual "send confirmation" repeat
     * @param type $id
     * @return type
     */
    public function sendConfirmationAction($id) {
        $this->buildAssets();
        
        $user = Users::findFirstById($id);
        if (!$user) {
            $this->flash->error("User was not found");
            return $this->dispatcher->forward(array(
                        'action' => 'index'
            ));
        }
        $user->mustChangePassword = 'Y';
        $user->save();

        if ($this->id->sendConfirmationEmail($user))
        {
            $this->flash->success("Confirm email sent");
        }
    }

    public function createNew() {

        $userForm = new UsersForm();
        $req = $this->request;
        if ($userForm->isValid($req->getPost())) {
            $user = new Users();
            $user->name = $req->getPost('name');
            $user->email = $req->getPost('email');
            
            $user->password = $this->security->hash($req->getPost('password'));
            $user->mustChangePassword = $req->getPost('mustChangePassword');
            $user->status = $req->getPost('status');

            if (!$user->save()) {
                foreach ($user->getMessages() as $message) {
                    $this->flash->error($message);
                }
                return false;
            }
            else {
                if ($user->status == 'C')
                {
                    /** assign user_group **/
                    $this->id->addRole($user->id, 'User');
                }
                $this->flash->success("User $user->name was created successfully");
                return $this->dispatcher->forward(
                        [
                    "controller" => "users",
                    "action" => "index"
                            ]);
            }
        }
    }

    /**
     * Saves a user edited
     *
     */
    public function saveAction() {

        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                        "controller" => "users",
                        "action" => "index"
            ));
        }

        $id = $this->request->getPost("id");

        $user = Users::findFirstByid($id);
        if (!$user) {
            $this->flash->error("user does not exist " . $id);

            return $this->dispatcher->forward(array(
                        "controller" => "users",
                        "action" => "index"
            ));
        }

        $user->id = $this->request->getPost("id");
        $user->name = $this->request->getPost("name");
        $user->email = $this->request->getPost("email", "email");
        $user->password = $this->request->getPost("password");
        $user->mustChangePassword = $this->request->getPost("mustChangePassword");
        $user->status = $this->request->getPost("status");


        if (!$user->save()) {

            foreach ($user->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                        "controller" => "users",
                        "action" => "edit",
                        "params" => array($user->id)
            ));
        }

        $this->flash->success("user was updated successfully");

        return $this->dispatcher->forward(array(
                    "controller" => "users",
                    "action" => "index"
        ));
    }

    private function listDelete($list)
    {
        $db = $this->getDb();
        $db->begin();
        foreach($list as $model)
        {
            if ($model->delete()===false)
            {
                foreach ($model->getMessages() as $message) {
                    $this->flash->error($message);
                }
                $db->rollback();
                return false;
            }
        }
        $db->commit();        
    }
    
    protected function checkPost()
    {
        if (!$this->request->isPost()  || !$this->security->checkToken())
        {
            $this->flash->error("Illegal Request");
            return $this->dispatcher->forward(array(
                        "controller" => "errors",
                        "action" => "index"
            ));
        }  
        return true;
    }
    public function sendConfirmAction() {
        if (!$this->checkPost())
        {
            return false;
        }
        $id = $this->request->getPost("userId");
        
        $user = Users::findFirstByid($id);
        if (!$user) {
            $this->flash->error("user was not found");

            return $this->dispatcher->forward(array(
                        "controller" => "errors",
                        "action" => "index"
            ));
        }
        if ($this->id->sendConfirmationEmail($user))
        {
            $this->flash->notice("Email sent");
             return $this->dispatcher->forward(array(
                        "controller" => "msg",
                        "action" => "index"
            ));           
        }
        else {
            $this->flash->error("Errors happened");

            return $this->dispatcher->forward(array(
                        "controller" => "errors",
                        "action" => "index"
            ));
           
        }
        
    }
    
    public function sendPasswordResetAction($id)
    {
        
        $userRec = Users::findFirstById($id);
        if ($userRec)
        {
            $this->id->sendPasswordReset($userRec,$this->request);
          
        }
        $this->dispatcher->forward(['controller' => 'users', 'action' => 'edit', 'id' => $id]);
                
                
    }
    /**
     * Deletes a user
     *
     * @param string $id
     */
    public function deleteAction() {
        
        if (!$this->checkPost())
        {
            return false;
        }
        $id = $this->request->getPost("userId");
        
        $user = Users::findFirstByid($id);
        if (!$user) {
            $this->flash->error("user was not found");

            return $this->dispatcher->forward(array(
                        "controller" => "users",
                        "action" => "index"
            ));
        }
        //** delete associated user events
        $events = UserEvent::findByuser_id($user->id);
        if (count($events) > 0)
        {
            $this->listDelete($events);
        }        
        $auth = UserAuth::findByuserId($user->id);
        if (count($auth) > 0)
        {
            $this->listDelete($auth);
        }
        
        if (!$user->delete()) {

            foreach ($user->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                        "controller" => "users",
                        "action" => "search"
            ));
        }

        $this->flash->success("user was deleted successfully");

        return $this->dispatcher->forward(array(
                    "controller" => "users",
                    "action" => "index"
        ));
    }

}
