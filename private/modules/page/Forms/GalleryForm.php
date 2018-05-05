<?php
/**
 * @author: Michael Rynn
 */
namespace Pcan\Forms;



use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;
use Phalcon\Forms\Element\TextArea;
use Phalcon\Forms\Element\Check;

use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Confirmation;
use Phalcon\Mvc\Model\Validator\Url as UrlValidator;

class GalleryForm extends Form {
    
    public function initialize($entity = null, $options = null)
    {
        $id = new Hidden('id');
        $this->add($id);
        
        $path= new Text('name', array('size' => 100, 'maxlength'=>255));
        $path->setLabel('Name');
        $this->add($path);
        $path->addValidators(array(
            new PresenceOf(array(
                'message' => 'Name is required for a Callery'
            )),
        ));
        $desc = new TextArea('description',array('rows'=> 6, 'cols' => 60));
        $desc->setLabel('Description');
        $this->add($desc);
                 
    }
    
}