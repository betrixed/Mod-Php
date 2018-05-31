<?php

/**
 * @author Michael Rynn
 */
namespace Page\Forms;

use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;
use Phalcon\Forms\Element\TextArea;

use Phalcon\Validation\Validator\PresenceOf;
use Mod\Forms\NiftyForm;


class LinkForm extends NiftyForm 
{
    
    public function initialize($entity = null, $options = null)
    {
        $id = new Hidden('id');
        $this->add($id);
        $id->setDefault("0");
        
        $id = new Text('sitename', array('size' => 60, 'maxlength'=>60));
        $id->setLabel('Site Name');
        $this->add($id);
        
        $textOptions = ['rows'=> 2, 
                    'cols' => 60,
                    'size' => 120, 
                    'maxlength'=>255
                    ];
        
        $id = new TextArea('title', $textOptions);
        $id->setLabel('Title');
        $id->addValidators(array(
            new PresenceOf(array(
                'message' => 'The title is required for a link'
            )),
        ));
        $this->add($id);
        
        $id = new TextArea('url',$textOptions );
        $id->setLabel('URL');
        $id->addValidators(array(
            new PresenceOf(array(
                'message' => 'The URL is required for a link'
            )),

        ));
        $this->add($id); 

        $id = new Select('enabled', [
            1 => 'Shown',
            0 => 'Hidden'
            ]);
        $id->setLabel('Enabled');
        $this->add($id);
        
        //Date clash with JS on chrome'
        
        $id = $this->makeDateTime('date_created','Date');

        
        $id = new Select('urltype', array(
            'Remote' => 'Remote',
            'Blog' => 'Blog',
            'Campaign' => 'Campaign',
            'Front' => 'Front Page',
            'Side' => 'Side Column',
            'Dash' => 'Dashboard Panel'
                )
                );
        $id->setLabel('URL Type');
        $this->add($id);
        
        $summary = new TextArea('summary',array('rows'=> 6, 'cols' => 60, 'style' => 'display:none;'));
        $summary->setLabel('Summary');
        $this->add($summary);
        
        
        $id = new Hidden('refid');
        $this->add($id);
                 
    }
}