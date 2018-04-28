<?php
namespace Secure\Models;

use Phalcon\Mvc\Model;

/**
 * SuccessLogins
 * This model registers successfull logins registered users have made
 */
class SuccessLogins extends Model
{

    /**
     *
     * @var integer
     */
    public $usersId;

    /**
     *
     * @var string
     */
    public $ipAddress;

    /**
     *
     * @var string
     */
    public $userAgent;

    /**
     *
     * @var string
     */
    public $created_at;
    
    
    public function initialize()
    {
        $this->belongsTo('usersId', 'Pcan\Models\Users', 'id', array(
            'alias' => 'user'
        ));
    }
}
