<?php
namespace App;

use Phalcon\Mvc\Model;
use Mod\Plugins\SendMail;

class Contact extends \Phalcon\Mvc\Model {
    public $id;
    
    public $name;
    
    public $telephone;
    
    public $email;
    
    public $body;
    
    public $sendDate;
    
    public $send_error;
    
    public $notify_error;
     /**
     * 
     */
    public function doSend()
    {
        $di = $this->getDI(); // magic
        
        $mailer = $di->getMail(); // SendMail
        $mailer->setModuleDir($this->module);
        $config = $di->get('config');
        $toName = $config->mail->toName;
        $toEmail = $config->mail->toEmail;
        // send to website contact info.
        // 
        // to, subject, name, params
        $errors = [];
        
        $templateData = array(
                    'name' => $this->name,
                    'email' => $this->email,
                    'telephone' => $this->telephone,
                    'body' => $this->body,
                    'sendDate' => $this->sendDate,
                    'publicUrl' => $config->pcan->publicUrl
                    );
        
        $templateHtml = $mailer->getHtmlTemplate('contact',$templateData);
        //$templateText = $mailer->getHtmlTemplate('contact',$templateData);
        
        $sentOk = $mailer->send(
                array(
                    $toEmail => $toName,
                ),
                'Website Contact',
                null,
                $templateHtml,
                $errors
                );
        if (!$sentOk)
        {
            if (count($errors)== 0)
            {
                $errors[] = "Failed to send";
            }
            $this->send_error = implode(PHP_EOL,$errors); 
        }
        $errors = [];
        $templateText = $mailer->getTextTemplate('contactrespond_text',$templateData);
        $templateHtml = $mailer->getHtmlTemplate('contactrespond',$templateData);

        $notifyOk = $mailer->send(
                array(
                    $this->email => $this->name,
                ),
                'Website Contact',
                $templateText,
                $templateHtml,
                $errors
                );
        if (!$notifyOk)
        {
            if (count($errors)== 0)
            {
                $errors[] = "Failed to Notify";
            }
            $this->notify_error = implode(PHP_EOL,$errors);
        }
        
        return ($sentOk && $notifyOk) ? true : false;
    }
    
}
