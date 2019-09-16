<?php
namespace Secure\Plugins;

use Mod\Path;
use Phalcon\Events\Event;
use Phalcon\Plugin;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Acl\Enum;
use Phalcon\Db\Enum as DbEnum;
use Phalcon\Acl\Role;
use Phalcon\Acl\Adapter\Memory as AclList;
use Phalcon\Acl\Component;

use Secure\Models\UserEvent;
use Secure\Models\ResetCode;
use Secure\Models\UserGroup;
use Secure\Models\UserAuth;

include_once  PHP_DIR . '/vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php';

class Security extends Plugin
{
    const SessionKey = 'auth';
    private $cacheAcl;
    public $userId;
    public $userName;
    public $userEmail;
    public $roleList;
    public $fbook;
    public $hasId;
    public $db;
    public $module;
    public $urlModule;
    public $controller;
    public $action;
    //public $config;
    public $isMobileFlag;
    public $device;
    public $agent;
    public $headers;
    
    //public $sessionId;
    
    public function getConfig()
    {
        if (empty($this->config))
        {
            $this->config = $this->getDI()->get('config');
        }
        return $this->config;
    }
    public function getDb()
    {
        if (is_null($this->db))
        {
            $this->db = $this->getDI()->get('db');
        }
        return $this->db;
    }
    
    public function getRoles()
    {
        return $this->roleList;
    }
    
    public function recordLogin($user)
    {
        /*
         * Get last login: 
         */
        $max_rows = 6;
        $db = $this->getDb();
        // get the most recent
        
        $stmt = $db->prepare("select created_at, status_ip as ipAddress, data as userAgent from user_event" 
                . " where user_id = :id and event_type = :event"
                . " ORDER BY created_at DESC"
                . " LIMIT :maxrows");  
        $stmt->bindValue(":id", $user->id, \PDO::PARAM_INT);
        $stmt->bindValue(":event", UserEvent::PW_LOGIN, \PDO::PARAM_STR);
        $stmt->bindValue(":maxrows", $max_rows, \PDO::PARAM_INT);
        
        $stmt->execute();
        
        $stmt->setFetchMode(DbEnum::FETCH_OBJ);    
        $data =  $stmt->fetchAll(); 

        if (is_array($data) && count($data) > 0)
        {
            if (count($data) == $max_rows)
            {
                $min_date = $data[$max_rows-1]->created_at;
                $stmt = $db->prepare("delete from user_event" 
                        . " where user_id = :id and event_type = :event"
                        . " and created_at < :mindate" );
                
                $stmt->bindValue(":id", $user->id, \PDO::PARAM_INT);
                $stmt->bindValue(":event", UserEvent::PW_LOGIN, \PDO::PARAM_STR);
                $stmt->bindValue(":mindate", $min_date, \PDO::PARAM_STR);
                $stmt->execute();

            }
            $last_login = $data[0];
        }
        else 
            $last_login = null;
        
        $this->session->set('last', $last_login);
        
        $success = new UserEvent();

        $success->user_id = $user->id;
        $success->status_ip = $this->request-> getClientAddress();
        $success->data = $this->request->getUserAgent();
        $success->event_type = UserEvent::PW_LOGIN;
        
        if (!$success->save())
        {
            $msglist = $success->getMessages();
            $this->flashErrors($msglist);
        }
    }    
    
    private function flashErrors($list)
    {
        $f = $this->getDI()->getFlash();
        $f->error(implode(PHP_EOL, $list));
    }
    private function returnErrors($model)
    {
        $this->flashErrors($model->getMessages());          
        return false;
    }
    
    public function sendConfirmationEmail($user)
    {
        $config = $this->getConfig();
        
        $templatesDir = $config->mail->templates;
        $code = $this->recordResetCode($user, UserEvent::EMAIL_CK, null);
        if (!$code)
            return false;
        $di = $this->di;
        $mailer = $di->getMail(); // SendMail
        $mailer->setModuleDir($this->module,$templatesDir);
        
        $templateData = [
            'userName' => $user->name,
            'publicUrl' => $config->pcan->publicUrl,
            'confirmUrl' => '/confirm/' . $code . '/' . $user->email
            ];
        
        $etext = $mailer->getTextTemplate('confirmation_text',$templateData);

        $html = $mailer->getHtmlTemplate('confirmation',$templateData );
        $errors = [];
        $sentOk = $mailer->send(
            [
                $user->email => $user->name
            ],
            "Please confirm your email", 
            $etext,
            $html,
            $errors
        );
        
        if (!$sentOk)
        {
            $this->flashErrors($errors);
        }
        
        return $sentOk;
    }
    
    static public function getResetCode()
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));
    }
    
    public function deleteOldResetCodes()
    {
        $db = $this->getDb();
        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval("P2D"));
        $ystr = $yesterday->format(DATETIME_FORMAT);
        $stmt = $db->prepare("delete from reset_code where created_at < :ydate");
        $stmt->bindValue(":ydate", $ystr, \PDO::PARAM_STR);
        $stmt->execute();
    }
    public function recordResetCode($user, $event_type, $request = null)
    {
        $this->deleteOldResetCodes();
        
        // Make a user event, and a resetcode
        $event = new UserEvent();
        $event->user_id = $user->id;
        $event->event_type = $event_type;
        if ($request)
        {
            $event->setRequestData($request);
        }
        else {
            $event->status_ip = "Confirm Email";
            $event->data = $user->email;
        }
        $event->created_at = Date('Y-m-d H:i:s');
        
        $resetcode = new ResetCode();
        $resetcode->code = SecurityPlugin::getResetCode();
        
        $resetcode->user_id = $user->id;
        $resetcode->created_at = $event->created_at;
        
        if (!$resetcode->save())
        {
            return $this->returnErrors($resetcode);
        }
        if (!$event->save())
        {
            return $this->returnErrors($event);
        }       
        
        return $resetcode->code;
    }
    public function sendPasswordReset($user, $request)
    {
        $di = $this->getDI();
        $config = $this->getConfig();
        
        $code = $this->recordResetCode($user, UserEvent::PW_RESET, $request);
        if (!$code)
            return false;
        
        $mailer = $di->getMail(); // SendMail
        $mailer->setModuleDir($this->module);
        $resetUrl = '/reset-password/' . $code . '/' . $user->name;
        
        $txdata = $mailer->getTextTemplate( 
                'reset_text', [
                    'resetUrl' => $resetUrl,
                    'publicUrl' => $config->pcan->publicUrl
                ]);
        
        $data = $mailer->getHtmlTemplate( 
                'reset', [
                    'resetUrl' => $resetUrl,
                    'publicUrl' => $config->pcan->publicUrl
                ]);
        
        $errors = [];
        $result = $mailer->send(
                array(
                    $user->email => $user->name
                ), 
                "Reset your password", 
                $txdata, 
                $data, 
                $errors
        );
        
        if (count($errors) > 0)
        {
            $this->flashErrors($errors);
        }
        else {
            $this->flash->notice('Reset password email sent');
        }
        return $result;
    }
    
    public function addRole($user_id, $roleName)
    {
        $group = UserGroup::findFirstByname($roleName);
        if ($group)
        {
            $role = new UserAuth();
            $role->groupId = $group->id;
            $role->userId = $user_id;
            if (!$role->save())
            {
                $f = $this->getDI()->getFlash();
                $f->error(implode(PHP_EOL , $role->getMessages()));    
            }
            else
                return true;
        }
        return false;
    }
    public function hasRole($roleName)
    {
        return is_array($this->roleList) ? in_array($roleName, $this->roleList) : false;
    }
    /**
     * Get Events of specific type for user
     * @param type $eventName
     * @param type $id
     * @return type
     */
    public function getUserEvents($eventName, $id)
    {
        $db = $this->getDb();
                 
        $stmt = $db->prepare(
            "select UE.status_ip, UE.data, UE.created_at from user_event UE" 
            . " where user_id = :id and event_type = :event order by UE.created_at DESC"
                );
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':event', $eventName, \PDO::PARAM_STR);
        $stmt->execute();
        $stmt->setFetchMode(DbEnum::FETCH_OBJ); 
        
        $data =  $stmt->fetchAll(); 
        return $data;
        
    }
    
    public function getUserSql($userId)
    {
        $db = $this->getDb();
        $sql = "SELECT * from users U"
                . " where ((U.email = :email1) OR (U.name = :email2))";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':email1', $userId, \PDO::PARAM_STR);
        $stmt->bindValue(':email2', $userId, \PDO::PARAM_STR);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        
        $result = $stmt->fetch();
        return $result;
    }
    
    private function getRoleList($userid)
    {
        $db = $this->getDI()->get('db');
        $sql = "SELECT  G.name from user_group G"
                . " join user_auth A on A.userId = :uid"
                . " and G.id = A.groupId and G.active = 1";
       $stmt = $db->prepare($sql);
       $stmt->bindValue(':uid', $userid, \PDO::PARAM_INT);
       $stmt->execute();
       $stmt->setFetchMode(\PDO::FETCH_COLUMN, 0);
       $roleList = ['Guest'];
       while( $result = $stmt->fetch())
       {
           $roleList[] = $result;
       }
       return $roleList;
        
    }
    
    public function getAuthKey($key)
    {
        $auth = $this->session->get(self::SessionKey);
        if (is_array($auth) && array_key_exists($key, $auth)) {
            return $auth[$key];
        }
        return null;
    }
    public function saveAuthKey($kec, $value)
    {
        $auth = $this->session->get(self::SessionKey);
        
        //$roleList = (!is_null($auth)) ? $auth->roles : ['Guest'];
        if (is_array($auth))
        {
            $auth[$key] = $value;
        }
        else {
            $auth = [$key => $value];
        }
        $this->session->set(
               self::SessionKey,
               $auth
        );
    }
    public function setSession($userdata)
    {

        $this->session->set(
            self::SessionKey,
            $userdata
        );
        
    }
    public function registerSession($user)
    {
        // lookup a role
        $roleList = $this->getRoleList($user->id);
        
        if(count($roleList) == 0)
        {
            $roleList[] = "Guest";
        }
        
        $userdata = array(
                'id'   => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roleList
            );
        
        
        $this->session->set(
            self::SessionKey,
            $userdata
        );
    }
    private function makeAcl()
    {
        $acl = new AclList();
        //$acl->setNoArgumentsDefaultAction(\Phalcon\Acl::DENY);
        $acl->setDefaultAction(Enum::DENY);
        
        // Roles are from the group table 
        // Guest indicates public permissions

        $db = $this->getDI()->get('db');
        
        $q_roles = $db->query("select name from user_group where active = 1");
        $q_roles->setFetchMode(DbEnum::FETCH_COLUMN,0);
        $roles = [];
        while($result = $q_roles->fetch())
        {
            $roles[] = new Role($result);
        }
        
        foreach ($roles as $role) {
            $acl->addRole($role);
        }   

        // private resources are for groupId <> guest role
        // order is critical to the loop
        
$sql=<<<EOD
select r.name as resource, r.action, ug.name from permissions p
 join resource r on r.id = p.resourceId
 join user_group ug on ug.id = p.groupId and ug.active=1
 order by  resource, action, name
EOD;
        $stmt = $db->query($sql);
        $stmt->setFetchMode(DbEnum::FETCH_OBJ);
        $results = $stmt->fetchAll();  // array of objects
        
        
        if ($results) {
            $actions = [];
            $resource = "";
            $pair = "";
            foreach($results as $row)
            {
                $test = $row->resource . "-" . $row->action;
                if ($test == $pair)
                {
                    continue;
                }
                else {
                    $pair = $test;
                }
                if ($row->resource != $resource)
                {
                    if (count($actions) > 0)
                    {
                        $acl->addComponent(new Component($resource), $actions);
                    }
                   
                    $resource = $row->resource; // reset
                    $actions = [];               
                }
                $actions[] = $row->action;
                
            }
            if (count($actions) > 0)
            {
                $acl->addComponent(new Component($resource), $actions);
            }
        }
        $acl->addComponent(new Component('admin/permissions'), ['index']);
        
        // Public area resources if empty permissions database
        $publicResources = array(
            'index'    => array('index','home','side'),
            'article'  => ['*'],
            'msg' => ['index'],
            'admin' . 'msg' => ['index'],
            'sitemap' => ['index'],
            'errors'   =>  ['*'],
            'admin' . 'errors'   => ['*'],
            'signup'   => array('subscribe'),
            'session'  => array('index', 'signup','start', 'end', 'forgotPassword'),
            'contact'  => array('index', 'send'),
            'user_control' => array('resetPassword')
        );
        foreach ($publicResources as $resource => $actions) {
            $acl->addComponent(new Component($resource), $actions);
        }
        
        // Grant access to public areas to all users which have Guest in role list
        foreach ($roles as $role) {
            foreach ($publicResources as $resource => $actions) {
                $acl->allow('Guest', $resource, $actions);
            }
        }

        // go to "permissions" table for groupId, resource, action, allow
       
        
        if ($results) {
            foreach($results as $row)
            {  
                $acl->allow($row->name, $row->resource, $row->action);
            }
        }
        $acl->allow('Admin', 'admin/permissions', 'index');
        return $acl;
    }
    protected function aclFilePath() {
        $config = $this->getConfig();
        $cacheDir = $config->cacheDir;
        return $cacheDir . "/aclcache.dat";
    }
    public function getAcl()
    {
        $config = $this->getConfig();
        
        if (isset($this->cacheAcl))
        {
            return $this->cacheAcl;
        }
        
        $aclfile = $this->aclFilePath();
        
        if (is_file($aclfile))
        {
            $this->cacheAcl =  unserialize(file_get_contents($aclfile));
        }
        else {
            $this->cacheAcl = $this->makeAcl();
            file_put_contents($aclfile, serialize($this->cacheAcl));
        }
        return $this->cacheAcl;
    }
    
    public function resetAcl()
    {
        $aclfile = $this->aclFilePath();
        unlink($aclfile); 
        unset($this->cacheAcl);
    }
    
    public function makeSecure() {
        $req = $this->request;
        if (!$req->isSecure()) {
            if (!Path::$config->useSSL)
                return true;
            $url = "https://" . $req->getHttpHost() . $req->getURI();
            $this->response->redirect($url);
            return false;
        }
        return true;
    }

    public function endSecure($uri) {
        $this->session->remove(self::SessionKey);
        //$req = $this->request;
        //$url = "http://" . $req->getHttpHost() . $uri;
        $this->response->redirect($uri);
    }
    
    public function readSession()
    {
        $auth = $this->session->get(self::SessionKey);
        //$roleList = (!is_null($auth)) ? $auth->roles : ['Guest'];
        if (is_array($auth))
        {
            $this->hasId = true;
            if (array_key_exists('roles', $auth))
            { 
                $this->roleList = $auth['roles'];
            }
            else {
                $this->roleList = ['Guest'];
            }
            if (array_key_exists('id',$auth))
            {
                $this->userId = $auth['id'];
            }
            if (array_key_exists('name',$auth))
            {
                $this->userName = $auth['name'];
            }
            if (array_key_exists('email',$auth))
            {
                $this->userEmail = $auth['email'];
            }
        }
        else {
            $this->hasId = false;
            $this->roleList = [0 => 'Guest'];
            $this->userName = 'Guest';
        }
        // any facebook user
        $fbook = $this->session->get('fbook');
        
        if (is_array($fbook))
        {
            $this->fbook = $fbook;
        }
        
    }
    
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        $this->readSession();
        // Take the active controller/action from the dispatcher
        
        // at this point, the dispatcher has a module, controller, action,
        // and also a views instance, with views dir as set for the module.
        
        
        $module = $dispatcher->getModuleName();
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        $this->module = $module;
        $this->controller = $controller;
        $this->action = $action;
        
        if ($controller === "errors") {
            return true;
        }
        $config = $this->getConfig();
        $ctx = $this->di->get('ctx');
        $mod_cfg = $ctx->getModuleConfig($module);
        
        if ($mod_cfg->isDefaultModule)
                $module = "";
        
        $this->urlModule = $module;
        

        if (!empty($mod_cfg))
        {
            if ($mod_cfg->has('roles')) {
                $roles = $mod_cfg->roles;
                foreach($roles as $role) {
                    if ($this->hasRole($role)) {
                        return true;
                    }
                }
            }
            else {
                $useAcl = $mod_cfg->useAcl ?? true;
                if (!$useAcl)
                    return true;
            }
        }

        // Obtain the ACL list
        $acl = $this->getAcl();

        // ACL access on combined module/controller resource
        
        $resourceName = strlen($module) > 0 ? $module . "/" . $controller : $controller;
        foreach($this->roleList as $role)
        {
            if ($acl->isAllowed($role, $resourceName, $action))
            {
                return true;
            }
        }
            // If he doesn't have access forward him to the index controller
       $msg =  "Not found: " . $resourceName . '/' . $action;      

        $this->flash->error($msg);

        $dispatcher->forward(
            array(
                'controller' => 'errors',
                'action'     => 'index'
            )
        );

        // Returning "false" we tell to the dispatcher to stop the current operation
        return false;
    }
    
    public function isMobile()
    {
        if (!isset($this->isMobileFlag))
        {
            $device = $this->getDevice();
            $this->isMobileFlag = $device->isMobile();
        }
        return  $this->isMobileFlag;
    }
    
    public function getDevice()
    {
       if (!isset($this->device))
       {
           $req = $this->request;
           $this->agent = $req->getUserAgent();
           $this->headers = $req->getHeaders();
           $this->device = new \Mobile_Detect($this->headers,$this->agent);
       }
       return $this->device;  
    }
}