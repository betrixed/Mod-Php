<?php
/**
 * @author Michael Rynn
 */
namespace Page\Controllers;

use Phalcon\Logger;
use Phalcon\DI;
use Phalcon\Exception;

use Mod\Path;
use Mod\App\Models\Contact;
use Mod\Plugins\Captcha;
use Page\Forms\ContactForm;

use Page\Models\Blog;
/**
 * Display the "About" page.
 */
class ContactController extends \Phalcon\Mvc\Controller
{
    protected function pickView($pick)
    {
        $view = $this->view;
        $this->ctx->pickView($view, $pick);
        $view->myModule = "/page/";
        $view->myController = "/page/contact/";
    }
    
    protected function buildAssets()
    {
        $elements = $this->elements;
        $elements->addAsset('bootstrap');
        //$elements->addAsset('salvattore');

        $elements->moduleCssList(['novosti.css','sbo.css'], 'page');

    }
     /**
     * Default action. Set the public layout (layouts/public.volt)
     */
    public function indexAction()
    {    
        $this->buildAssets();
        $this->setupForm();
    }

    private function setupForm()
    {
        $request = $this->request;
        $config = Path::$config;
        $google = $config->google->toArray();
       
        if ($config->exists('contact'))
        {
            $contact = $this->config->contact;

        
            $conditions = ['article' => $contact->article];
            $blog = Blog::findFirst([
                'conditions' => "title_clean=:article:",
                'bind' => $conditions,
                    ]);

            
        }
        else {
            $blog = new Blog();
            $blog->style = 'noclass';
            $blog->article = "<strong>Generic email form</strong>";
        }
        $this->view->blog = $blog;
        $this->view->google = $google;
        $this->view->title = "Contact";

        $this->pickView("about/contact");        
        $cform = new ContactForm();
        $this->view->form = $cform;             
        if ($request->isPost()==false)
        {


            $this->view->donePost = false;
            return;
        }
           // handle Post
        try {
            if ($google['recaptcha'])
            {
                $captcha = new Captcha($google['captchaPrivate']);
                $resp = $captcha->checkRequest($request);
                if (!$resp)
                {
                    throw new Exception("Google Captcha Error");
                }
            }     
            
            if ($cform->isValid($this->request->getPost()) != false) {  
                // isValid does not assign data?
                $postdata = array(
                    'name' => $this->request->getPost('name', 'striptags'),
                    'telephone' => $this->request->getPost('telephone', 'striptags'),
                    'email' => $this->request->getPost('email', 'email'),
                    'body' => $this->request->getPost('body', 'striptags')
                );
                $crecord = new Contact();
                $crecord->assign($postdata); // remember data?
                $crecord->sendDate = date('Y-m-d H:i:s');
                
                if (!$crecord->doSend())
                {
                    $msg = "";
                    if (isset($crecord->send_error))
                    {
                        $msg .=  $crecord->send_error;
                    }
                    if (isset($crecord->notify_error))
                    {
                        if (count($msg) > 0)
                            $msg .= PHP_EOL;
                        $msg .= $crecord->notify_error;
                    }
                        
                    $this->flash->notice($msg); 
                }
                 
                
                $this->view->donePost = true;
                
                if ($crecord->save())
                {
                    $this->flash->notice("Message Sent!"); 
                } 
                else {
                    $this->flash->notice("Unable to Save");   
                    $this->flash->notice($crecord->getMessages());
                }
            }
            else {
                $collect = '';
                foreach($cform->getMessages() as $message )
                {
                    if (strlen($collect) > 0)
                        $collect .= PHP_EOL;
                    $collect .= "Problem with field " .$message->getField() . ': ' .  $message->getMessage();
                }
                $this->flash->notice($collect); 
                $this->view->errors = $cform->getMessages();
            }
        }
        catch(Exception $ex)
        {
            $this->flash->notice($ex->getMessage());
        }
        return;
    }
    
}