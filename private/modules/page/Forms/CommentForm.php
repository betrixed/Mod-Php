<?php

namespace Page\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;
use Phalcon\Forms\Element\TextArea;
use Phalcon\Forms\Element\Check;

use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Confirmation;

class CommentForm extends Form 
{
    public function initialize($entity = null, $options = null)
    {
        
        
        $id = new Hidden('id');
        $this->add($id);
        
        $id = new Hidden('enabled');
        $this->add($id);
        
        $id = new Hidden('blog_id');
        
        $this->add($id);
        
        $id = new Hidden('user_id');
        $this->add($id);
        
        $id = new Hidden('head_id');
        $this->add($id);        
        
        $id = new Hidden('reply_to_id');
        $this->add($id);
        
        $comment = new TextArea('comment');
        $this->add($comment);
        
       
        $title = new Text('title',array(
            'placeholder' => 'Title',
            'size' => 100));
        $this->add($title);
        
        $check = new Check('enabled');
        $this->add($check);
        
        $check = new Check('mark_read');
        $this->add($check);
        

    }
}