<?php

namespace Secure\Controllers;

use Mod\Google\Captcha;
use Secure\Forms\SignUpForm;
use Secure\Forms\ForgotPasswordForm;
use Secure\Forms\ChangePasswordForm;
use Secure\Models\Users;
use Secure\Models\UserEvent;

class SessionController extends \Phalcon\Mvc\Controller
{
    public $token;
    public $google;
    public $vparams;
    public $acl;
    public $mod;
    public $ctx;
    
    protected function captchaCheck()
    {
        $captcha = new Captcha($this->google['captchaPrivate']);
        return $captcha->checkRequest($this->request);
    }
    
    public function initialize()
    {
        //$this->initBase();
        $this->ctx = $this->di->get('ctx');
        $this->acl = $this->ctx->acl;
        $this->mod = $this->ctx->activeModule;
        
        $this->tag->setTitle('Sign Up/Sign In');
        
        $this->google = $this->mod->google;
        
        //$this->view->setTemplateBefore('id');
    }
    
    public function registerAction()
    {
        $view = $this->view;
        $this->ctx->pickView($view, 'signup/index');
    }
    
    protected function buildAssets()
    {
        $this->elements->addAsset('bootstrap');
    }
    protected function getLoginParams()
    {
        $formAction = '/secure/session/start';
        $this->buildAssets();
        $vparams['controller'] = 'session';
        $vparams['action' ] = 'index';
        $vparams['google'] = $this->google;
        $vparams['formAction'] = $formAction;  
        return $vparams;
    }
    
    public function indexAction()
    {
        if (!$this->acl->makeSecure())
            return false;
        
        
        if (!$this->request->isPost()) {
            $ctx = $this->di->get('ctx');
            $vparams = $this->getLoginParams();
            $this->buildAssets();
            return $ctx->getExplicitResponse($vparams);

        }
    }


    

    private function loginPost()
    {
            // Get the data from the user
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        if ($this->google['loginCaptcha'])
        {
            if (!$this->captchaCheck())
                return false;
        }
        try {
            $sec = $this->acl;
            $user = $sec->getUserSql($email);
            if ($user) {
                 if ($this->security->checkHash($password, $user->password)) 
                 {
                     $sec->registerSession($user);
                     $sec->recordLogin($user);
                     return $user;
                 }
            } else {
        // To protect against timing attacks. Regardless of whether a user exists or not, the script will take roughly the same amount as it will always be computing a hash.
                $this->security->hash(rand());
                
            }
        }
        catch(Exception $ex)
        {
            $this->flash->error($ex->getMessage());
        }    
        
        return false;
    }
    public function loginAction()
    {
        $req = $this->request;
        if (!$this->requestSecure($req) && $this->useSSL)
        {
            $url = "https://" . $req->getHttpHost() . $req->getURI();
            $this->response->redirect($url);
            return false;
        }
        if ($this->request->isPost()) {
            $user = $this->loginPost();
            if ($user)
            {
                $this->view->disable();
                $this->response->redirect('/secure/dashboard/index');
                return false;
            }
            else {
                    $this->view->disable();
                    $this->response->redirect('/secure/id/index');
                    return false;
            }
        }
        else {
            $vparams = $this->getLoginParams();
            return $this->getExplicitResponse($vparams);
        }
    }
    
    private function setFormAction()
    {
            $this->buildAssets();
            $formAction = $this->myController . "start";
            
            $this->view->formAction = $formAction;  
            $this->view->google = $this->google;
    }
    /**
     * This action authenticate and logs a user into the application
     */
    public function startAction()
    {
        if ($this->request->isPost()) {
            $user = $this->loginPost();
            if ($user != false) {
                $this->flash->success('Welcome ' . $user->name);

                // Forward to the 'invoices' controller if the user is valid
                /*$dispatcher = $this->di->get('dispatcher');
                $dispatcher->forward( ['action'=>'index', 'controller'=>'dashboard']);
                 * 
                 */
                $this->response->redirect('/secure/dashboard/index');
                return true;
            }
            $this->flash->error('Wrong email/password');
        }

        // Forward to the login form again
        return $this->response->redirect('id/index');
    }
    /**
     * Finishes the active session redirecting to the index
     *
     * @return unknown
     */
    public function endAction()
    {
        $this->session->remove('auth');
        $this->flash->success('Goodbye!');
        $this->endSecure('/index/index');
        return false;
    }
    
    public function signupAction() {
        if (!$this->acl->makeSecure())
            return false;
        
        $this->buildAssets();
        $security = $this->getDI()->get('security');
        if (!$this->request->isPost()) {
            
            $this->token = $security->getToken(); // generate CSRF in session
        }
        
        $config = $this->config;

        $form = new SignUpForm();
        $this->view->form = $form;
        $this->view->google = $this->google;
        $this->view->title = $config->pcan->shortName . ' Signup';
        
        $this->ctx->pickView($this->view, 'session/signup');
        
        if ($this->request->isPost()) {
            $request = $this->request;
            try {
                // Captcha check new signups first
                if ($this->google['signupCaptcha'])
                {
                    if (!$this->captchaCheck())
                    {
                        $this->flash->error("Google thinks you are a robot or interloper");
                        $this->view->form->resetCSRF();
                        return;
                    }
                }
                if ($form->isValid($this->request->getPost()) != false) {
                    
                    $user = new Users();
                    $name = $this->request->getPost('name', 'striptags');
                    $email = $this->request->getPost('email','email');
                    $password =  $this->security->hash($this->request->getPost('password'));
                    
                    $match = Users::findFirstByEmail($email);
                    if ($match)
                    {
                        $this->flash->notice('Email already exists');
                        return;
                    }
                   
                    $user->assign(array(
                        'name' => $name,
                        'email' => $email,
                        'password' => $password
                    ));
                    if ($user->save()) {
                        
                        $check = $this->id->sendConfirmationEmail($user);
                        if ($check) {
                            $this->flash->notice('Confirm link has been sent to ' . $email);
                        }
                        return $this->dispatcher->forward(array(
                                    'controller' => 'msg',
                                    'action' => 'index'
                        ));
                    }
                    $msgs = $user->getMessages();
                    if (is_array($msgs))
                    {
                        $text = join(PHP_EOL, $msgs);
                        $this->flash->notice($text);
                    }
                    
                }
                else {
                    $collect = '';
                    foreach($form->getMessages() as $msg)
                    {
                        $collect .= $msg->getMessage() . PHP_EOL;
                    }
                    $this->view->form->resetCSRF();
                    $this->flash->notice($collect);
                }
            } catch (AuthException $e) {
                $this->flash->notice($e->getMessage());
            }
        } 
    }
    /**
     * Users must use this action to change its password
     */
    public function changePasswordAction()
    {
        $this->buildAssets();
        $form = new ChangePasswordForm();
        
        $this->ctx->pickView($this->view, 'session/changePassword');
        $this->view->myController = "/secure/id/";
        
        if ($this->request->isPost()) {

            if (!$form->isValid($this->request->getPost())) {

                foreach ($form->getMessages() as $message) {
                    $this->flash->error($message);
                }
            } else {
                $sec = $this->acl;
                
                $userId = $sec->userId;
                
                $user = Users::findFirstById($userId);

                $user->password = $this->security->hash($this->request->getPost('password'));
                $user->mustChangePassword = 0;

                $passwordChange = new UserEvent();
                $passwordChange->user_id = $user->id;
                $passwordChange->setRequestData($this->request);
                $passwordChange->event_type = UserEvent::PW_CHANGE;

                if (!$passwordChange->save()) {
                    $this->flash->error($passwordChange->getMessages());
                } else {

                    $this->flash->success('Your password was successfully changed');
                    $dispatcher = $this->di->get('dispatcher');
                    $dispatcher->forward( ['controller' => 'dashboard', 'action' =>'index']);
                    return false;
                }
            }
        }

        $this->view->form = $form;
    }
    public function forgotPasswordAction() {
        $this->buildAssets();
        
        $form = new ForgotPasswordForm();

        if ($this->request->isPost()) {

            if ($form->isValid($this->request->getPost()) == false) {
                foreach ($form->getMessages() as $message) {
                    $this->flash->error($message);
                }
            } else {

                $user = Users::findFirstByEmail($this->request->getPost('email'));
                if (!$user) {
                    $this->flash->success('There is no account for this email');
                } else {
                    if ($this->id->sendPasswordReset($user, $this->request))
                    {
                        $this->flash->success('Success! Please check your messages for an email reset password');
                    }
                    else {
                        $this->flash->success('Password reset failed');
                    }
                }
            }
        }
        $view = $this->view;
        $this->ctx->pickView($view,'session/forgotPassword');
        $this->buildAssets();
        $view->form = $form;
    }
}