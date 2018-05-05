<?php

namespace Admin\Controllers;

use Admin\Models\MenuItem;
use Admin\Models\MenuLink;

use Admin\Forms\MenuItemForm;
use Admin\Forms\MenuLinkForm;

use Phalcon\Db\Column; 
use Pcan\Plugins\MenuTree;

class MenuController extends \Phalcon\Mvc\Controller {
    protected function setView($action) {
        $view = $this->view;
        $this->ctx->pickView($view, $action);
        $view->myModule = '/admin/';
        $view->myController = '/admin/menu/';
        $view->setTemplateBefore('index');
    }
    protected function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
    }
    
    public function resetMenuCache($name)
    {
        $tree = new MenuTree();
        $tree->resetMenuCache($name);
    }
    public function submenuAction()
    {
        if ($this->request->isPost())
        {
            $req = $this->request;

            $textId = $req->getPost('id','int');
            
            $id = is_numeric($textId) ? intval($textId) : 0;
            $isMenu = true;
            
            $controller = $req->getPost('controller','striptags');
            $mclass =$req->getPost('class','striptags');
            $this->view->menuId = null;
            return;
        }
        $form = new MenuItemForm(null, ['isMenu' => false]);
        $this->buildAssets();
        $this->view->menuId = null;
        $this->view->isMenu = true;
        $this->view->form = $form;
    }    
    public function subitemAction()
    {
        
        $view = $this->view;
        
        if ($this->request->isPost())
        {    
            $req = $this->request;

            $textId = $req->getPost('id','int');

            $id = is_numeric($textId) ? intval($textId) : 0;
            $isMenu = false;

            $controller = $req->getPost('controller','striptags');
            $mclass =$req->getPost('class','striptags');
            if ($id > 0)
            {
                $item = MenuItem::findFirst($id);
                $isMenu = is_null($item->controller);
            }
            else {
                $item = new MenuItem();
                $isMenu = (is_null($controller) || (strlen($controller) == 0)) ? true : false;
            }
            if (is_null($mclass) || strlen($mclass)==0)
                $mclass = 'noclass';
            if (!$isMenu)
            {
                $item->setAction($req->getPost('action','striptags'));
                $item->setController($controller);
            }
            $item->setClass($mclass);
            $item->setUserRole($req->getPost('user_role','striptags'));
            $item->setCaption($req->getPost('caption','striptags'));
            $item->setLangId($req->getPost('lang_id','int'));
            
            if (!$item->save())
            {
                $messages = $item->getMessages();

                foreach ($messages as $message) {
                    $this->flash->error($message);
                }
            }
            else {
                $msg =  ($id > 0) ? 'Menu updated' : 'Menu Created';
                $this->flash->success($msg);   
            }
            $view->isMenu = $isMenu;
            $view->menuId = $id;
            $menuView = $isMenu ? 'menu/submenu': 'menu/subitem';
            $this->setView($menuView);
            $form = new MenuItemForm($item, ['isMenu' => $isMenu]);

        }
        else {
            $isMenu = false;
            $view->menuId = null;
            $view->isMenu = $isMenu;
            $this->setView('menu/item');
            $form = new MenuItemForm(null, ['isMenu' => $isMenu]);
        }
        $this->buildAssets();

        $view->form = $form;
    }
    public function indexAction()
    {
        
        $this->buildAssets();
        $db = $this->db;
$sql =<<<DOD
select distinct M.caption as name, M.id from menu_item M 
    join menu_link L where M.id = L.menu_item_id and L.menu_top_id = -1;
    order by name
DOD;
        $stmt = $db->query($sql);
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        $qres = $stmt->fetchAll();
        $view = $this->view;
        $view->menulist = $qres;
        $this->setView('menu/index');
    }
    
    public function resetAction()
    {
        $this->buildAssets();
        $this->pickView('menu/edit');
        $rootid = intval($this->request->get('m0','int'));
        $menu = MenuItem::findFirstById($rootid);
        if ($menu)
        {
            $this->resetMenuCache($menu->caption);
            $this->view->menuName = $menu->caption;
        }
        $this->getMenuTree($rootid);   
    }
    
    protected function getMenuQuery($sql)
    {
         $db = $this->db;
         $stmt1 = $db->query($sql);
         $stmt1->setFetchMode(\Phalcon\Db::FETCH_OBJ);
         $results1 = $stmt1->fetchAll();
         return $results1;
    }
    
    private function newMenuLinkForm()
    {
        $form = new MenuLinkForm();

$msql1=<<<EOD
    select M.id, M.caption 
        from menu_item M 
        
EOD;

            $results1 = $this->getMenuQuery($msql1);       
            $form->makeSelectList($results1,"menu_item_id", "Menu");

$msql2=<<<EOD
    select M.id, M.caption from menu_item M 
        where M.controller is null or M.controller='' 
EOD;
        $results2 = $this->getMenuQuery($msql2);
        $form->makeSelectList($results2,"menu_top_id", "Parent"); 
        return $form;
    }
    
    private function validatorLinkForm()
    {
        $form = new MenuLinkForm();
        $form->makeText("menu_item_id", "Menu");
        $form->makeText("menu_top_id", "Menu");
        $form->setValidation($form::getLinkValid());
        return $form;
    }
    
    public function delitemAction($id)
    {
        if ($id > 0)
        {
            $msql= "delete from menu_item where id = :mid";
            $db = $this->db;
            $prep = $db->prepare($msql);
            $prep->bindValue(':mid', $id, \PDO::PARAM_INT);
            if ($prep->execute())
            {
                $this->flash->success('Menu item deleted');
                $this->dispatcher->forward(
                        [ 'action' => 'list']
                        );
                return;
            }
            else {
                $this->flash->error('Deletion error');
            }
            
        }
        else {
            $this->flash->error('This item must not be deleted');
        }
        $this->dispatcher->forward(
                        [ 'action' => 'list']
                        );
        
    }
    
    public function linkAction()
    {
        $this->buildAssets();
        $view = $this->view;
        $this->setView('menu/link');
        
        $view->allowUnlink = false;
        if ($this->request->isGet())
        {
            //* list of menus 
            
            $idtext = $this->request->get('id','int');
            $linktext = $this->request->get('link','int');
            $form = $this->newMenuLinkForm();
            
            if (strlen($idtext) > 0 && strlen($linktext) > 0)
            {
                $rec = MenuLink::findFirst(
                    ['conditions' => 'menu_item_id = ?1 and menu_top_id = ?2',
                     'bind' => [
                         1 => intval($idtext),
                         2 => intval($linktext)
                     ]]
                ); 
                if ($rec)
                {
                    $form->setEntity($rec);
                    $this->view->allowUnlink = true;
                    $this->view->id = $rec->menu_item_id;
                    $this->view->link = $rec->menu_top_id;
                }
            }
            else {
                if (strlen($idtext) > 0)
                {
                    $element = $form->get('menu_item_id');
                    $element->setDefault($idtext);
                }
            }
            
            $view->form = $form;
            
        }
        else if ($this->request->isPost())
        {
            $form = $this->validatorLinkForm();
            $view->form = $form;  
            $link = new MenuLink();
            $form->bind($_POST, $link);
            if ($form->isValid())
            {
                $link->save();
 
            }

        }

    }
    public function unlinkAction()
    {

        $child = intval($this->request->get('id','int'));
        $parent = intval($this->request->get('link','int'));
        
        if (is_int($child) && is_int($parent))
        {
            $db = $this->db;
            
            $success = $db->execute(
                    'delete from menu_link where menu_item_id = ? and menu_top_id = ?',
                         [ $child, $parent]);
        }
        $this->buildAssets();
        $this->setView('menu/list');
        $this->listAction();

    }
    
    protected function getMenuTree($rootid)
    {
$menu_sql = <<<DOD
select 0 as level, 0 as link, 0 as serial, ML.* from menu_item ML 
    join (select distinct L1.menu_item_id as h1, L1.serial
        from menu_link L1 join menu_item M on L1.menu_item_id = M.id and M.id = :rootid
        where L1.menu_top_id = -1
    ) J1 on ML.id = h1
UNION
select 1 as level, J1.h0, J1.serial, ML.* from menu_item ML 
    join (select distinct L2.menu_item_id as h1, L1.menu_item_id as h0, L2.serial
        from menu_link L1 join menu_item M on L1.menu_item_id = M.id and M.id = :rootid
        left outer join menu_link L2 on L2.menu_top_id = L1.menu_item_id
        where L1.menu_top_id = -1 
    ) J1 on ML.id = h1
UNION
select 2 as level, J1.h0, J1.serial, ML.* from menu_item ML 
    join (select distinct L3.menu_item_id as h1, L2.menu_item_id as h0, L3.serial
        from menu_link L1 join menu_item M on L1.menu_item_id = M.id and M.id = :rootid
        left outer join menu_link L2 on L2.menu_top_id = L1.menu_item_id
        left outer join menu_link L3 on L3.menu_top_id = L2.menu_item_id
        where L1.menu_top_id = -1
    ) J1 on ML.id = h1      
UNION
select 3 as level, J1.h0, J1.serial, ML.* from menu_item ML 
    join (select distinct L4.menu_item_id as h1, L3.menu_item_id as h0, L4.serial
        from menu_link L1 join menu_item M on  L1.menu_item_id = M.id and M.id = :rootid
        left outer join menu_link L2 on L2.menu_top_id = L1.menu_item_id
        left outer join menu_link L3 on L3.menu_top_id = L2.menu_item_id
        left outer join menu_link L4 on L4.menu_top_id = L3.menu_item_id
        where L1.menu_top_id = -1
    ) J1 on ML.id = h1   
DOD;
        
        $db = $this->db;
        
        $prep = $db->prepare($menu_sql);
        
        $result = $db->executePrepared($prep, 
                [
                    "rootid" => $rootid
                ],
                [
                    "rootid" => Column::BIND_PARAM_INT,
                ]
                );

        $result->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        
        $results = $result->fetchAll();  
        
        if (count($results) > 0)
        {
            $this->view->menuName  = $results[0]->caption;
        }
        
        $this->view->rootid = $rootid;
        $this->view->menulist = $results;        
    }
    
    /**
     * Access to all links regardless of which menu tree, by item
     */
    public function listAction()
    {
$menu_sql= <<<FOD
select L.menu_top_id as link, L.serial, 
    ML.* from menu_item ML left outer join  menu_link L
    on L.menu_item_id = ML.id
    order by link, serial
FOD;
        $db = $this->db;      
        $result = $db->query($menu_sql);
        $result->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        $results = $result->fetchAll();  
        $this->view->menulist = $results;   
        $this->buildAssets();
        $this->setView('menu/list');
    }
    
    public function itemAction($id)
    {
        $this->buildAssets();
        $item = MenuItem::findFirst($id);
        if ($item)
        {
            $isMenu = is_null($item->controller);
            $options =  ($isMenu) ? ['isMenu' => true ] : [];
                
            $form = new MenuItemForm($item,$options);
            $this->view->form = $form;
            $this->view->isMenu = $isMenu;
            $this->view->menuId = $id;
            $menuView = $isMenu ? 'menu/submenu': 'menu/subitem';
            $this->setView($menuView);
        }
    }
    public function editAction()
    {
        if (!$this->request->isGet())
            return;
        $rootid = $this->request->get('m0','int');
        $this->getMenuTree($rootid);
        $this->buildAssets();
        $this->setView('menu/edit');
    }
}