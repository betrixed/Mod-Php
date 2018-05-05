<?php

namespace Mod\Plugins;
/**
 *  Cacheable menu hierachy by language id
 */
use Phalcon\Mvc\User\Component;
use Mod\Plugins\Menu\Menu;

class MenuTree extends Component {
    
    private $menuTree;
    
    public function getCacheName($menuName)
    {
        $config = $this->config;
        $menufile = $config->configCache . "/menu_" . $menuName . "_cache.dat";
        return $menufile;

    }
    public function resetMenuCache($menuName)
    {
        $menufile = $this->getCacheName($menuName);
        unlink($menufile);
    }
    public function getMainMenu($menuName)
    {
        if (isset($this->menuTree))
        {
            return $this->menuTree;
        }
        
        $menufile = $this->getCacheName($menuName);
        
        if (is_file($menufile))
        {
            $this->menuTree =  unserialize(file_get_contents($menufile));
        }
        else {
            $this->menuTree = $this->makeMenu($menuName);
            file_put_contents($menufile, serialize($this->menuTree));
        }

        return $this->menuTree;
    }
    
    private function makeMenu($menuName)
    {

        $db = $this->getDI()->get('db');
        

$menu_sql = <<<EOD
select 0 as level, 0 as link, 0 as serial, ML.* from menu_item ML 
    join (select distinct L1.menu_item_id as h1, L1.serial
        from menu_link L1 join menu_item M on M.id = L1.menu_item_id and M.caption = '$menuName'
        where L1.menu_top_id = -1
    ) J1 on ML.id = h1
UNION
select 1 as level, J1.h0, J1.serial, ML.* from menu_item ML 
    join (select distinct L2.menu_item_id as h1, L1.menu_item_id as h0, L2.serial
        from menu_link L1 join menu_item M on M.id = L1.menu_item_id and M.caption = '$menuName'
        left outer join menu_link L2 on L2.menu_top_id = L1.menu_item_id
        where L1.menu_top_id = -1
    ) J1 on ML.id = h1
UNION
select 2 as level, J1.h0, J1.serial, ML.* from menu_item ML 
    join (select distinct L3.menu_item_id as h1, L2.menu_item_id as h0, L3.serial
        from menu_link L1 join menu_item M on M.id = L1.menu_item_id and M.caption = '$menuName'
        left outer join menu_link L2 on L2.menu_top_id = L1.menu_item_id
        left outer join menu_link L3 on L3.menu_top_id = L2.menu_item_id
        where L1.menu_top_id = -1
    ) J1 on ML.id = h1      
UNION
select 3 as level, J1.h0, J1.serial, ML.* from menu_item ML 
    join (select distinct L4.menu_item_id as h1, L3.menu_item_id as h0, L4.serial
        from menu_link L1 join menu_item M on M.id = L1.menu_item_id and M.caption = '$menuName'
        left outer join menu_link L2 on L2.menu_top_id = L1.menu_item_id
        left outer join menu_link L3 on L3.menu_top_id = L2.menu_item_id
        left outer join menu_link L4 on L4.menu_top_id = L3.menu_item_id
        where L1.menu_top_id = -1
    ) J1 on ML.id = h1         
EOD;
               
        $stmt = $db->query($menu_sql);
        $stmt->setFetchMode(\Phalcon\Db::FETCH_OBJ);
        
        $results = $stmt->fetchAll();
        
        $topItem = null;
        $prevLevel = null;
        $thisLevel = [];
        $level = -1;
        $parent = -1;
        $parentMenu = null;
        foreach($results as $row)
        {
            
            $item = new Menu();
            $item->id = intval($row->id);
            
            
            if ($row->level > $level)
            {
                $level = $row->level;
                if ($level == 0) $topItem = $item;
                $prevLevel = $thisLevel;
                $parent = 0;
            }        
            
            if (!empty($row->controller) || !empty($row->action))
            {
                $item->action = $row->action; 
                $item->controller = $row->controller;
                // can't be parent
            }
            else {
                $thisLevel[$item->id] = $item;
            }
            
            $parentId = intval($row->link);
            if (($parentId > 0) && array_key_exists($parentId, $prevLevel))
            {
                if ($parent != $parentId)
                {
                    $parentMenu = $prevLevel[$parentId];
                    
                    

                }
                
            }
            if ($parentMenu)
            {
               $parent = $parentId;
               $item->parent = $parentMenu;
               $parentMenu->addItem($item);
            }           
            $item->caption = $row->caption;
            $item->serial = intval($row->serial);

            if (!is_null($row->user_role))
            {
                $item->restrict = $row->user_role;
            }
            // lookup parent must be previous level

                

        }
        if (is_null($topItem))
        {
            $topItem = new Menu();
            $topitem->caption = "Home";
            $topItem->action="index";
            $topItem->controller="index";
        }
        $topItem->class = 'navbar-left';

        
        return $topItem;
    }
    
};
