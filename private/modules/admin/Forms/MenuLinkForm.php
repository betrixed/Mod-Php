<?php

/**
 * @author Michael Rynn
 */
namespace Admin\Forms;

use Mod\Forms\NiftyForm;

use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Select;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Callback;

/**
 * Description of MenuLinkForm
 *
 * @author michael
 */
class MenuLinkForm extends NiftyForm {
    public function initialize($entity = null, $options = null)
    {
        $this->makeTextDefault("serial", "Order","0");
    }
    static function isInteger($val)
    {
        return is_int($val) ? true : false;
    }    
    static function isRecordId($val)
    {
        return is_int($val) && (intval($val) > 0) ? true : false;
    }
    static function getLinkValid()
    {
        $valid = new Validation();
        $valid->add(
            'menu_item_id',
            new Callback(
                    [
                        'callback' => function($data) {
                                return !MenuLinkForm::isRecordId($data['menu_item_id']);
                        },
                        'message' => 'Must be menu reference'
                    ])
                );
        
        $valid->add(
            'menu_top_id',
            new Callback(
                    [
                        'callback' => function($data) {
                                return !MenuLinkForm::isRecordId($data['menu_top_id']);
                        },
                        'message' => 'Must be menu reference'
                    ])
                );
        $valid->add(
            'serial',
            new Callback(
                    [
                        'callback' => function($data) {
                                return !MenuLinkForm::isInteger($data['serial']);
                        },
                        'message' => 'Must be integer'
                    ])
                );
        return $valid;
    }
    
    
    public function makeText($fieldName, $label)
    {
        $name = new Text($fieldName);
        $name->setLabel($label);
        $this->add($name);
        return $name;
    }
    public function makeSelectList($menuList,$fieldName, $label)
    {
        // options set as array [ id => caption ' ]
            $options = [0 => "-"];
            $name = $fieldName;
            foreach($menuList as $menu)
            {
                $options[ $menu->id ] = $menu->caption . " (" . $menu->id . ")";
            }
            $sel = new Select($name, $options);
            $sel->setLabel($label);
            $this->add($sel);
            return $sel;
    }
}
