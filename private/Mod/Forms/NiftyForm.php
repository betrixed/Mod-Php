<?php

namespace Mod\Forms;

use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\TextArea;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;
use Phalcon\Validation\Validator\PresenceOf;

class NiftyForm extends \Phalcon\Forms\Form {
    protected $scripts;
    
    protected function addAttributes($element, array $list)
    {
        $attr = $element->getAttributes();
        $attr = array_merge($attr,$list);
        $element->setAttributes($attr);
    }
    protected function hiddenId($fieldName)
    {
        $id = new Hidden($fieldName);
        $id->setDefault("0");
        $this->add($id);
        return $id;
    }
    protected function readOnlyId($fieldName)
    {
        $id = new Text($fieldName, ['readonly' => 'readonly']);
        $this->add($id);
        return $id;
    }    
    
    protected function makeTextDefault($fieldName, $labelName, $default)
    {
        $name = new Text($fieldName);    
        
        $name->setLabel($labelName);

        $name->setDefault($default);

        $this->add($name);
        
        return $name;
    }

    /**
     * Requires making $sArray as 'key' => 'value'
     * @param type $sArray
     * @param type $fieldName
     * @param type $label
     * @return Select
     */
    protected function makeSelect($sArray, $fieldName, $label)
    {
            $sel = new Select($fieldName, $sArray);
            $sel->setLabel($label);
            $this->add($sel);
            return $sel;
    }
    protected function makeTextReq($fieldName, $labelName = null)
    {
        $name = new Text($fieldName, [
            'placeholder' => $fieldName,
        ]);
        if (!empty($labelName))
        {
            $name->setLabel($labelName);
        }

        $name->addValidators(array(
            new PresenceOf(array(
                'message' => "$labelName is required"
                    ))
        ));

        $this->add($name);
        
        return $name;
    }
    protected function makeDate($fieldName, $labelName)
    {
        $id = new Text($fieldName); 
        $id->setLabel($labelName);
        $id->setUserOptions(['jstype' => 'date']);
        
        $id->setDefault( date("Y-m-d"));
        $this->add($id);  
        
        return $id;
    }    
    protected function makeDateTime($fieldName, $labelName)
    {
        $id = new Text($fieldName); 
        $id->setLabel($labelName);
        $id->setUserOptions(['jstype' => 'datetime']);
        
        $id->setDefault( date("Y-m-d H:m:s"));
        $this->add($id);  
        
        return $id;
    }
    protected function makeTextArea($fieldName, $labelName=null, $options=null)
    {
        if (is_null($options))
        {
            $options = [
                    'rows'=> 3, 
                    'cols' => 60,
                    'size' => 120, 
                    'maxlength'=>255
                    ];              
        }

        $name = new TextArea($fieldName, $options);
        
        if (!empty($labelName))
        {
            $name->setLabel($labelName);
        }
        $this->add($name);
        
        return $name;
    }
    
    static function renderLabel($for,$label)
    {
        echo '<label for="',$for , '" class="control-label"' , '>', 
                $label, "</label>" ;
    }
    
    public function renderGroup($multiple)
    {
        echo '<div class="form-group">' . PHP_EOL;
            foreach($multiple as $name)
            {
                $this->renderName($name);
            }
        echo "</div>" . PHP_EOL; // formgroup
    }
    public function renderName($name)
    {
         $element  = $this->get($name);

        // Get any generated messages for the current element
        $messages = $this->getMessagesFor( $name );

        if (count($messages)) {
            // Print each element
            echo '<div class="messages">';

            foreach ($messages as $message) {
                echo $this->flash->error($message);
            }

            echo "</div>" . PHP_EOL;
        }

        //$isDate = is_a ($element,"Phalcon\Forms\Element\Date" );
        $jsType = $element->getUserOption('jstype');
        $isDate = ($jsType == 'date' || $jsType == 'datetime') ? true : false;
        
        $label = $element->getLabel();
        if ( $isDate )
        {
            echo "<div class='input-group date'>";
            $this->emitJSDate($name);
        }
         
        if ($isDate)
        {   
            if (!empty($label))
                $this::renderLabel($name,$label);
             echo "<span class='input-group-addon'>";
             
            echo $element,  PHP_EOL;
            echo "<span class='glyphicon glyphicon-calendar'></span>",
                            "</span>" . PHP_EOL;
        }
        else {
            
            if (!empty($label))
                $this::renderLabel($name,$label);
            echo $element;
        }
        if ($isDate)
        {
            echo "</div>" . PHP_EOL; // input-group date
        }       
    }
    public function renderCustom($name)
    {
        echo '<div class="form-group">' . PHP_EOL;
        $this->renderName($name);
        echo "</div>" . PHP_EOL; // formgroup
    }    
    
    /**
     * Prints messages for a specific element
     */
    public function messages($name)
    {
        if ($this->hasMessagesFor($name)) {
            foreach ($this->getMessagesFor($name) as $message) {
                $this->flash->error($message);
            }
        }
    }
    
    public function emitJS()
    {
        if (!empty($this->scripts))
        {
            foreach($this->scripts as $js)
            {
                echo $js . PHP_EOL;
            }
        }
    }
    
    public function addJS($script)
    {
        if (!empty($this->scripts))
        {
            $this->scripts[] = $script;
        }
        else {
            $this->scripts = [$script];
        }        
    }
    public function emitJSDate($byId)
    {
$script=<<<EOD
<script type="text/javascript">
    $(function () {
        var opt = {
            format: 'YYYY-MM-DD'
        };
        var ct = $('#$byId');
        ct.datetimepicker(opt);
    })();
</script>         
EOD;
        $this->addJS($script);
    }
    public function emitJSDateTime($byId)
    {
$script=<<<EOD
    <script type="text/javascript">
    $(function () {
        var opt = {
            format: 'YYYY-MM-DD HH:mm'
        };
        var ct = $('#$byId');
        ct.datetimepicker(opt);
    })();
</script>       
EOD;
        $this->addJS($script);
    }   
    
}