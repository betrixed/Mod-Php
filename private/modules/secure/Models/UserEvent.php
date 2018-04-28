<?php
namespace Secure\Models;

use Phalcon\Mvc\Model;
use Phalcon\Http\Request;
/**
 * UserEvent
 * This model registers successful logins, passwords, emails, resets
 */
class UserEvent extends Model
{
    const PW_LOGIN = "PW_LOGIN";
    const PW_RESET = "PW_RESET";
    const PW_CHANGE = "PW_ALTER";
    const EMAIL_CK = "EMAIL_CK";
    const PW_TOKEN = "PW_TOKEN";
    
    /**
     *
     * @var integer
     */
    public $user_id;

    /**
     *
     * @var string
     */
    public $created_at;
    
    /**
     *
     * @var string
     */
    public $event_type;

    
    /**
     *
     * @var string
     */
    public $data;
    
    /**
     *
     * @var string
     */
    public $status_ip;
    
    public function initialize()
    {
        $this->belongsTo('user_id', 'Pcan\Models\Users', 'id', array(
            'alias' => 'user'
        ));
    }
    /** get ipAddress and userAgent from request */
    
    public function setRequestData($req)
    {
        $this->data = $req->getUserAgent();
        $this->status_ip = $req->getClientAddress();
    }
    static public function getEventTypes()
    {
        return [
            PW_LOGIN => "Login",
            PW_RESET => "Password reset",
            EMAIL_CK => "Confirm Email",
            PW_CHANGE => "Password change",
            PW_TOKEN => "Token Access",           
        ];
    }
}
