<?php
namespace Secure\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Check;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Identical;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Confirmation;


class SignUpForm extends \Mod\Forms\NiftyForm
{
    public $hasTerms = false;
    
    protected function makePassword($name, $label)
    {
        $pw = new Password($name);
        $pw->setLabel($label);
        $pw->addValidators([
            new PresenceOf([
                'message' => "$label is required"
            ])
        ]);
        $this->add($pw);
        return $pw;
    }
    
    public function initialize($entity = null, $options = null)
    {
        $name = $this->makeTextReq('name','Name');
        
        $this->addAttributes($name,['size' => 60, 'maxlength'=>60]);
                
        

        // Email
        $email = $this->makeTextReq('email','Email');
        $this->addAttributes($email,['size' => 50, 'maxlength'=>45]);

        $email->addValidators([
            new Email([
                'message' => 'The e-mail is not valid'
            ])
        ]);



        // Password
        $password = $this->makePassword('password','Password');

        $password->addValidators([
            new StringLength([
                'min' => 8,
                'messageMinimum' => 'Password is too short. Minimum 8 characters'
            ]),
            new Confirmation([
                'message' => 'Password doesn\'t match confirmation',
                'with' => 'confirmPassword'
            ])
        ]);

        // Confirm Password
        $confirmPassword = $this->makePassword('confirmPassword','Confirm Password');


        // Remember
        if ($this->hasTerms)
        {
            $terms = new Check('terms', array(
                'value' => 'yes'
            ));
            $this->add($terms);
        }
            /*
        $terms->setLabel('Accept terms and conditions');

        $terms->addValidator(new Identical(array(
            'value' => 'yes',
            'message' => 'Terms and conditions must be accepted'
        )));

        $this->add($terms);
             
             */

        // CSRF
        $this->resetCSRF();

        // Sign Up
        $this->add(new Submit('Sign Up', array(
            'class' => 'btn btn-success'
        )));
    }
    public function resetCSRF()
    {
        if($this->has('csrf'))
        {
             $this->remove('csrf');
        }
        $csrf = new Hidden('csrf');
            
        
        $csrf->addValidator(new Identical(array(
            'value' => $this->security->getSessionToken(),
            'message' => 'CSRF validation failed'
        )));

        $this->add($csrf);
    }

}
