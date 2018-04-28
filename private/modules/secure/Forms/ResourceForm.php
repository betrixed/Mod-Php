<?php

namespace Secure\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;
use Phalcon\Validation\Validator\PresenceOf;


class ResourceForm extends Form {
    public function initialize($entity = null, $options = null)
    {
        $id = new Hidden('id');
        $this->add($id);

        $name = new Text('name', array(
            'placeholder' => 'Name',
           'size' => 20,
        ));

        $name->addValidators(array(
            new PresenceOf(array(
                'message' => 'Attribute name is required'
            ))
        ));
        $name->setLabel('Name');
        $this->add($name);

        $action = new Text('action', array(
            'placeholder' => 'Action',
            'size' => 80,

        ));

        $action->addValidators(array(
            new PresenceOf(array(
                'message' => 'Action Value is required'
            )),
        ));
        $action->setLabel('Action');
        $this->add($action);
 
    }
}