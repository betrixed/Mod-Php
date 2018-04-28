<?php

/**
 * @author Michael Rynn
 */

namespace Secure\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Confirmation;

class UsersForm extends Form {


    public function initialize($entity = null, $options = null) {

        // For edit the id is hidden
        $isEdit = isset($options['edit']) && $options['edit'];
        $myid = isset($options['myid']) ? $options['myid'] : null;

        /*if (isset($entity) && isset($myid) && ($myid === $entity->id)) {
            $id = new Text('id', [
                'readonly' => 'readonly', 
                'placeholder' => 'Id'
            ]);
            //$id = new Hidden('id');
        } else {
           // $id = new Text('id' , ['readonly' => 'readonly']);
            $id = new Hidden('id');
        }
        
        */
        $id = new Hidden('id');
        $this->add($id);

        $name = new Text('name', array(
            'placeholder' => 'Name'
        ));
        $name->setLabel('Name');

        $name->addValidators(array(
            new PresenceOf(array(
                'message' => 'Name is required'
                    ))
        ));

        $this->add($name);

        $emailOptions = $isEdit ? ['readonly' => 'readonly'] 
                            : ['placeholder' => 'email@email.domain.abc'];

        $email = new Text('email', $emailOptions);

        $email->addValidators(array(
            new PresenceOf(array(
                'message' => 'The e-mail is required'
                    )),
            new Email(array(
                'message' => 'The e-mail is not valid'
                    ))
        ));
        $email->setLabel('Email');
        $this->add($email);

        if (!$isEdit) {
            $password = new Password("password", ['placeholder' => 'Password']);
            $password->addValidators([
                    new PresenceOf([
                        'message' => 'Password is required'
                    ]),
                    new StringLength([
                        'min' => 8,
                        'messageMinimum' => 'Password is too short, min 8 characters',
                    ]),
                    new Confirmation([
                        'message' => 'Password confirmation does not match',
                        'with' => 'passwordCheck'
                    ])              
            ]);
            $password->setLabel('Password');
            $this->add($password);

            $passwordCheck = new Password("passwordCheck", ['placeholder' => 'Check Password']);

            $passwordCheck->addValidators([
                    new PresenceOf([
                        'message' => 'Password confirm is required',
                    ])
            ]);
            $passwordCheck->setLabel('Confirm');
            $this->add($passwordCheck);
        }
        $status = new Select('status', [
            'C' => 'Confirmed',
            'N' => 'New'
                ]);
        $status->setLabel('Status');
        $this->add($status);
        
        $changePW = new Select('mustChangePassword', [
            'Y' => 'Yes',
            'N' => 'No'
                ]);
        $changePW->setLabel('Force change password');
        $this->add($changePW);

    }
    
    public function renderCustom($name)
    {
        echo '<div class="formgroup">';
        $element  = $this->get($name);

        // Get any generated messages for the current element
        $messages = $this->getMessagesFor(
            $element->getName()
        );

        if (count($messages)) {
            // Print each element
            echo '<div class="messages">';

            foreach ($messages as $message) {
                echo $this->flash->error($message);
            }

            echo "</div>";
        }

        echo "<p>";

        echo '<label for="', $element->getName(), '">', $element->getLabel(), "</label>";

        echo $element;

        echo "</p>";
        echo "</div>";
    }

}
