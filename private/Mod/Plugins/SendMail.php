<?php
namespace Mod\Plugins;

require_once __DIR__ . '/../../vendor/swiftmailer/swiftmailer/lib/swift_required.php';
        
use Phalcon\Mvc\User\Component;
use Swift_Message as Message;
use Swift_SmtpTransport as Smtp;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Simple;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;

/**
 * Pcan\Mail\Mail
 * Sends e-mails based on pre-defined templates
 */
class SendMail extends Component
{
    protected $transport;
    protected $module;
    protected $templateDir;
    /**
     * Applies a template to be used in the e-mail , at ACTION_VIEW only
     *
     * @param string $name
     * @param array $params
     */
    
    private function getViewEngine()
    {
        $voltCache = $this->config->pcan->cacheDir . "volt/";
        return ['.volt' => function ($view, $di) use($voltCache) {
            $volt = new VoltEngine($view, $di);
            
            $volt->setOptions(array(
                "compiledPath" => $voltCache,
                'compiledSeparator' => '_'
            ));

            $compiler = $volt->getCompiler();
            $compiler->addFunction('is_a', 'is_a');

            return $volt;
        }];
    }
    
    public function setModuleDir($module, $folderName = null)
    {
        $this->module = $module;
        if (isset($folderName))
        {
             $this->templateDir = $folderName;
        }
        else {
            $this->templateDir = $this->config->mail->templates;
        }
    }
    
    static function viewExists($viewName)
    {
        return (file_exists($viewName . ".volt"));
    }   
    
    private function makeEmailView($name)
    {
        $view = new View();
        $view->setDI($this->getDI());
        $view->registerEngines($this->getViewEngine());
        
        $controllerAction = $this->templateDir . "/" . $name;
       
        $module = $this->module;
        $viewModule = (strlen($module) > 0) ? $module : "app";
        
        $mod = $this->config->get($viewModule);
        
        // make ordered list of paths to look for $controllerAction
        $testArray = [$mod->viewsDir, 
                      PCAN_DIR . "views_" . $mod->name . "/", 
                      PCAN_DIR . "views/"];
        
        foreach($testArray as $testDir)
        {
            if ($this::viewExists($testDir . $controllerAction))
            {
                $this->viewsDir = $testDir;
                $view->setViewsDir($testDir);
                $view->pick($controllerAction);
                break;
            }
        }
        return $view;
        
    }
    public function getTextTemplate($name, $params)
    {
        $view = $this->makeEmailView($name);
        $result = $view->getRender($this->templateDir, $name, $params, 
                function($view) {
                    $view->setRenderLevel(View::LEVEL_ACTION_VIEW);
                }
        );
        
        return $result;

    }
    
      /**
     * Applies a template to be used in the e-mail for text at VIEW level
     *
     * @param string $name
     * @param array $params
     */
    public function getHtmlTemplate($name, $params)
    {
        $view = $this->makeEmailView($name);
        //$result = $view->render('emailTemplates', $name, $params);
        $result = $view->getRender($this->templateDir, $name, $params, 
                function($view) {
                    $view->setRenderLevel(View::LEVEL_LAYOUT);
                }
        );
        
        return $result;
    }  

    /**
     * Sends e-mails via AmazonSES based on predefined templates
     *
     * @param array $to
     * @param string $subject
     * @param string $textMail
     * @param array $htmlMail : optional
     */
    public function send($to, $subject, $textMail, $htmlMail, &$errors = NULL)
    {
        // Settings
        $mailSettings = $this->config->mail;

        // Create the message
        $message = Message::newInstance()
            ->setSubject($subject)
            ->setTo($to)
            ->setFrom(array(
                $mailSettings->fromEmail => $mailSettings->fromName
            ));
        
        $hasBody = false;
        if (isset($textMail) && strlen($textMail) > 0)
        {
            $hasBody = true;
            $message->setBody($textMail);
        }
        
        if (isset($htmlMail) && strlen($htmlMail) > 0)
        {
            if ($hasBody)
                $message->addPart($htmlMail, 'text/html');
            else
                $message->setBody($htmlMail, 'text/html');
        }

        if (!$this->transport) {
            $this->transport = Smtp::newInstance(
                $mailSettings->smtp->server,
                $mailSettings->smtp->port,
                $mailSettings->smtp->security
            )
            ->setUsername($mailSettings->smtp->username)
            ->setPassword($mailSettings->smtp->password);
        }

        // Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance($this->transport);
        $result = True;
        try {
            $mailer->send($message);
        }
        catch(\Exception $e)
        {
            $result = False;
            if (isset($errors))
                $errors[] = $e->getMessage();
        }
        return $result;
    }
}
