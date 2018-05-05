<?php

/**
 * @author Michael Rynn
 */
namespace Admin\Forms;


class MenuItemForm extends \Mod\Forms\NiftyForm 
{
    public function initialize($entity = null, $options = null)
    {
        
        $this->hiddenId("id");
        $this->makeTextDefault("lang_id", "Language","-1");
        $this->makeTextReq("caption", "Caption");
        
        $test = is_object($entity);

        if ($test )
        {
            $test2 = is_null($entity->controller);
            $isMenu = $test2;
        }
        else {
            $isMenu = (is_array($options) && array_key_exists('isMenu',$options)) ?
                $options['isMenu'] : false;
        }     
        if (!$isMenu)
        {
            $this->makeTextReq("controller", "Controller");
            $this->makeTextReq("action", "Action");
           
        }
         $this->makeTextReq("class", "CSS class");
        $this->makeTextReq("user_role", "User Role");
        
        
    }

}