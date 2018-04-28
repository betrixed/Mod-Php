<?php
/**
 * @author Michael Rynn
 */
namespace Pcan\Controllers;

use Pcan\Models\ResetCode;
use Pcan\Models\Users;
use Pcan\Plugins\SecurityPlugin;

class UserControlController extends BaseController
{

    public function indexAction()
    {

    }

    /**
     * Confirms an e-mail, if the user must change thier password then changes it
     */
    public function confirmEmailAction()
    {
        $this->buildAssets();
        
        $code = $this->dispatcher->getParam('code');

        $resetcode = ResetCode::findFirstByCode($code);

        if (!$resetcode) {
            return $this->resetError('Confirm code not found');
        }

        $created_at = date_create_from_format(DATETIME_FORMAT, $resetcode->created_at);
        $rightnow = new \DateTime();
        $interval = $created_at->diff($rightnow);
        
        if ($interval->days > 3)
        {
            return $this->resetError('Confirm code has expired');
        }
        
        $user_id = $resetcode->user_id;

        $user = Users::findFirstById($user_id);
        
        if (!$user)
        {    
            $this->flash->error('Associated user not found');

            return $this->dispatcher->forward(array(
                'controller' => 'msg',
                'action' => 'index'
            ));          
        }
        /**
         * Add user to group 'User'
         * It has to happen before register Session
         */
        $this->id->addRole($user->id, 'User');
        /* reset session */
        $this->session->remove('auth');
        $this->id->registerSession($user);
        /**
         * Check if the user must change his/her password
         */

        if ($user->mustChangePassword == 'Y') {

            $this->flash->success('The email was successfully confirmed. Please change your password now.');

            return $this->dispatcher->forward(array(
                'controller' => 'id',
                'action' => 'changePassword'
            ));
        }

        $this->flash->success('The email was successfully confirmed');

        return $this->dispatcher->forward(array(
            'controller' => 'msg',
            'action' => 'index'
        ));
    }

    private function resetError($msg)
    {
        $this->flash->error($msg);
            
        return $this->dispatcher->forward(array(
            'controller' => 'errors',
            'action' => 'index'
        ));       
    }
    public function resetPasswordAction()
    {
        $code = $this->dispatcher->getParam('code');

        $resetcode = ResetCode::findFirstByCode($code);

        if (!$resetcode) {
            return $this->resetError("Reset code does not exist, Try Again?");
         }
        
        $user_id = $resetcode->user_id;
        $created_at = date_create_from_format(DATETIME_FORMAT, $resetcode->created_at);
        $rightnow = new \DateTime();
        $interval = $created_at->diff($rightnow);
        
        if ($interval->days > 1)
        {
            return $this->resetError('Reset code has expired');
        }
        
        $resetcode->delete();
        // need to get user record and call securityplugin to register session
        $sec = $this->id;
        
        $user = Users::findFirstById($user_id);
        
        if ($user != false)
        {
            $sec->registerSession($user);
            $this->flash->success('One time login for reset password');

            return $this->dispatcher->forward(array(
                'controller' => 'session',
                'action' => 'changePassword'
            ));          
        }
    }
}
