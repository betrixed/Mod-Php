<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Page\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;
use Phalcon\Validation\Validator\PresenceOf;

/**
 * Description of MetaForm
 *
 * @author Michael Rynn
 */


class MetaForm extends Form {
    //put your code here
    public function initialize($entity = null, $options = null)
    {
 

        $id = new Hidden('id');
        $this->add($id);

        $name = new Text('meta_name', array(
            'placeholder' => 'Name',
           'size' => 20,
        ));

        $name->addValidators(array(
            new PresenceOf(array(
                'message' => 'Attribute name is required'
            ))
        ));

        $this->add($name);

        $atvalue = new Text('template', array(
            'placeholder' => 'Template',
            'size' => 80,

        ));

        $atvalue->addValidators(array(
            new PresenceOf(array(
                'message' => 'Attribute Value is required'
            )),
        ));

        $this->add($atvalue);

        $content = new Text('data_limit', array(
            'placeholder' => 'Size Limit',
            'size' => 10,

        ));
        $this->add($content);

        $this->add(new Select('display', array(
            '1' => 'Yes',
            '0' => 'No'
                )
                ));       
    }
}
