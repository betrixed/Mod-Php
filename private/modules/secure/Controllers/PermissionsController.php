<?php
namespace Secure\Controllers;

use Secure\Models\UserGroup;
use Secure\Models\Permissions;
use Secure\Models\Resource;
use Secure\Models\ResourceActions;
/**
 * View and define permissions for the various profile levels.
 */
class PermissionsController extends \Phalcon\Mvc\Controller
{
    protected function buildAssets()
    {
        $this->elements->addAsset('bootstrap');
    }
    public function indexAction()
    {
        return $this->defaultAction();
    }
    /**
     * View the permissions for a profile level, and change them if we have a POST.
     */
    
    public function resetAction()
    {
        $id = $this->getSecurityId();
        $id->resetAcl();
        $this->flash->success('Permissions were updated with success');
        $this->defaultAction();
    }
    public function defaultAction()
    {
        $this->buildAssets();
        $view = $this->view;
        $view->setVar('myModule', "/secure/");
        $view->setVar("hasData", false);
        $this->ctx->pickView($view,'permissions/index');
        if ($this->request->isPost() ) {
             $submit = $this->request->getPost('submit');
             $group = UserGroup::findFirstById($this->request->getPost('groupId'));
             
             if ($group) {
                $id = $this->acl;
                if ($submit == 'Fetch')
                {
                    $this->view->setVar("hasData", true); 
                }
                else if ($submit == 'Update' && $this->request->hasPost('permissions')) {
                    
                    // Deletes the current permissions      
                    $group->getPermissions()->delete();

                    // Save the new permissions
                    foreach ($this->request->getPost('permissions') as $permission) {

                        $parts = explode('.', $permission);

                        $permission = new Permissions();
                        $permission->groupId = $group->id;
                        $permission->resourceId = intval($parts[1]);

                        $permission->save();
                    }
                    $id->resetAcl();
                    $this->flash->success('Permissions were updated with success');
                    $view->setVar("hasData", true);
                    
                }
                
                
                // Rebuild the ACL with
                //$this->acl->rebuild();
                
                
                $resources = \Pcan\Models\Resource::find(['order' => 'name, action']);
                $rlist = [];
                $ct = 0;
                $resAction = null;
                foreach($resources as $item)
                {
                    if ($ct == 0 || $resAction->name != $item->name)
                    {
                        $ct = 1;
                        $resAction = new ResourceActions($item->name);
                        $rlist[] = $resAction;
                    }
                    $resAction->addAction($item->action, $item->id);
                }
                
                $view->acl = $rlist;
                // Pass the current permissions to the view
                $db = $this->getDb();
                $stmt = $db->query("select r.id, r.name, r. action, p.groupId, g.name as `group` from resource r"
                        . " join permissions p on r.id = p.resourceId"
                        . " join user_group g on g.id = p.groupId" 
                        . " order by name, action, `group`");
                
                $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
                $permissions = $stmt->fetchAll();  
                
                // Construct positive values of resource . action
                $plist = [];
                foreach($permissions as $gperm)
                {
                    if ($gperm->groupId == $group->id)
                    {
                        $plist[ "r." . $gperm->id] = true;
                    }
                    else
                        $glist[ "r." . $gperm->id][] = " " . $gperm->group;
                }
                $view->plist = $plist;
                $view->glist = $glist;
            }

            $view->group = $group;
        }
       
        // Pass all the active profiles
         $view->groups = UserGroup::find('active = 1');
    }
}
