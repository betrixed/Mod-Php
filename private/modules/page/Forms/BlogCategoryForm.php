<?php

namespace Page\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;

use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Confirmation;
use Phalcon\Validation\Validator\Email;


class BlogCategoryForm extends Form
{

    public function initialize($entity = null, $options = null)
    {
        /* $isEdit = isset($options['edit']) && $options['edit'];
        $myid = isset($options['myid']) ? $options['myid'] : null;
        if (isset($entity) && isset($myid) && ($myid === $entity->id)) {
         **/
         
            $id = new Text('id', array(
                'readonly'=>'readonly', 'placeholder' => 'Id'
            ));
            //$id = new Hidden('id');
            $this->add($id);
      //  } 
        $name = new Text('name', array(
            'placeholder' => 'Name'
        ));

        $name->addValidators(array(
            new PresenceOf(array(
                'message' => 'The name is required'
            ))
        ));
        $name->setLabel("Name");
        $this->add($name);
        
        $name_clean = new Text('name_clean', array(
            'placeholder' => 'Name_clean'
        ));
        $name_clean->setLabel("URL part");
        $this->add($name_clean);
        
        $select = new Select('enabled', array(
            1 => 'Yes',
            0 => 'No'
                )
        
                );
        $select->setLabel("Enabled");
        $this->add($select);  
    }
    
    
    
}